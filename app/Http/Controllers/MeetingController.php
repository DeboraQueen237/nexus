<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Meeting::class);

        $userId = $request->user()->id;
        $tab = $request->get('tab', 'upcoming');
        $view = $request->get('view', 'list');

        if ($view === 'calendar') {
            return $this->calendarView($request, $userId);
        }

        $query = Meeting::query()
            ->with(['organizer'])
            ->where(fn ($q) => $q->where('user_id', $userId)
                ->orWhereHas('participants', fn ($p) => $p->where('user_id', $userId)));

        if ($tab === 'past') {
            $query->where(fn ($q) => $q->where('status', 'ended')->orWhere('end_time', '<', now()));
        } else {
            $query->where(fn ($q) => $q->whereIn('status', ['scheduled', 'ongoing']));
        }

        $meetings = $query->orderBy('start_time', $tab === 'past' ? 'desc' : 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('meetings.index', [
            'meetings' => $meetings,
            'tab' => $tab,
        ]);
    }

    protected function calendarView(Request $request, int $userId): View
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $cursor = \Carbon\Carbon::create($year, $month, 1);

        $meetings = Meeting::query()
            ->where(fn ($q) => $q->where('user_id', $userId)
                ->orWhereHas('participants', fn ($p) => $p->where('user_id', $userId)))
            ->whereBetween('start_time', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($m) => $m->start_time->format('Y-m-d'));

        $selectedDate = $request->get('date');

        return view('meetings.calendar', [
            'cursor' => $cursor,
            'meetingsByDay' => $meetings,
            'selectedDate' => $selectedDate,
            'dayMeetings' => $selectedDate ? ($meetings->get($selectedDate) ?? collect()) : collect(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Meeting::class);

        return view('meetings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Meeting::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'max_participants' => ['nullable', 'integer', 'min:2', 'max:50'],
            'allow_link_join' => ['boolean'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $meeting = Meeting::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'meeting_url' => '',
            'invite_token' => Str::random(32),
            'allow_link_join' => $request->boolean('allow_link_join', true),
            'platform' => 'webrtc',
            'status' => 'scheduled',
            'max_participants' => $data['max_participants'] ?? 12,
        ]);

        $meeting->update(['meeting_url' => $meeting->inviteUrl()]);

        if (! empty($data['participants'])) {
            $attach = [];
            foreach (array_unique($data['participants']) as $participantId) {
                if ((int) $participantId === $request->user()->id) continue;
                $attach[$participantId] = ['status' => 'invited'];
            }
            $meeting->participants()->attach($attach);
        }

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion planifiée.');
    }

    public function show(Request $request, Meeting $meeting): View
    {
        Gate::authorize('view', $meeting);

        $meeting->load(['organizer', 'participants']);

        $myStatus = $meeting->user_id === $request->user()->id
            ? 'organizer'
            : $meeting->participants->firstWhere('id', $request->user()->id)?->pivot->status ?? 'guest';

        return view('meetings.show', [
            'meeting' => $meeting,
            'myStatus' => $myStatus,
            'canJoinNow' => Gate::allows('join', $meeting) && ! $meeting->isEnded() && $meeting->status !== 'cancelled',
        ]);
    }

    public function edit(Meeting $meeting): View
    {
        Gate::authorize('update', $meeting);

        $meeting->load('participants');

        return view('meetings.edit', ['meeting' => $meeting]);
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        Gate::authorize('update', $meeting);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'allow_link_join' => ['boolean'],
        ]);

        $meeting->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'allow_link_join' => $request->boolean('allow_link_join', true),
        ]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion mise à jour.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        Gate::authorize('delete', $meeting);

        $meeting->update(['status' => 'cancelled']);

        return redirect()->route('meetings.index')->with('success', 'Réunion annulée.');
    }

    public function respond(Request $request, Meeting $meeting): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:accepted,declined']]);

        $participant = $meeting->participants()->where('user_id', $request->user()->id)->first();

        if (! $participant) {
            abort(403, "Tu n'es pas invité à cette réunion.");
        }

        $meeting->participants()->updateExistingPivot($request->user()->id, ['status' => $data['status']]);

        return back()->with('success', $data['status'] === 'accepted' ? 'Invitation acceptée.' : 'Invitation déclinée.');
    }

    public function joinByLink(Request $request, string $token)
    {
        $meeting = Meeting::where('invite_token', $token)->firstOrFail();

        if (! $meeting->allow_link_join) {
            abort(403, "Cette réunion n'accepte pas les participants via lien.");
        }

        return redirect()->route('meetings.show', $meeting);
    }

    public function room(Request $request, Meeting $meeting): View
    {
        Gate::authorize('join', $meeting);

        if ($meeting->status === 'scheduled') {
            $meeting->update(['status' => 'ongoing']);
        }

        if ($request->user()->id !== $meeting->user_id) {
            $meeting->participants()->syncWithoutDetaching([
                $request->user()->id => ['status' => 'joined', 'joined_at' => now()],
            ]);
        }

        return view('meetings.room', [
            'meeting' => $meeting,
        ]);
    }

    public function end(Request $request, Meeting $meeting): RedirectResponse
    {
        Gate::authorize('update', $meeting);

        $meeting->update(['status' => 'ended', 'end_time' => now()]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Réunion terminée.');
    }
}
