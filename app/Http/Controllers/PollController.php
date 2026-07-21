<?php

namespace App\Http\Controllers;

use App\Events\PollVoted;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PollController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Poll::class);

        $polls = Poll::query()
            ->with(['user', 'options'])
            ->when($request->get('filter') === 'mine', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $userVotes = PollVote::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('poll_option_id', $polls->getCollection()->flatMap->options->pluck('id'))
            ->pluck('poll_option_id');

        $polls->getCollection()->transform(fn (Poll $poll) => $this->formatPoll($poll, $userVotes));

        return view('polls.index', compact('polls'));
    }

    public function create(): View
    {
        Gate::authorize('create', Poll::class);

        return view('polls.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Poll::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:single,multiple'],
            'is_anonymous' => ['boolean'],
            'is_public' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:255'],
        ]);

        $poll = Poll::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'is_anonymous' => $request->boolean('is_anonymous'),
            'is_public' => $request->boolean('is_public', true),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        foreach ($data['options'] as $optionText) {
            $poll->options()->create(['option_text' => $optionText]);
        }

        return redirect()->route('polls.show', $poll)->with('success', 'Sondage publié avec succès.');
    }

    public function show(Request $request, Poll $poll): View
    {
        Gate::authorize('view', $poll);

        $poll->load(['user', 'options']);

        $userVotes = PollVote::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('poll_option_id', $poll->options->pluck('id'))
            ->pluck('poll_option_id');

        return view('polls.show', [
            'poll' => $this->formatPoll($poll, $userVotes),
        ]);
    }

    public function vote(Request $request, Poll $poll)
    {
        Gate::authorize('vote', $poll);

        $rules = $poll->type === 'single'
            ? ['option_id' => ['required', 'integer', 'exists:poll_options,id']]
            : ['option_ids' => ['required', 'array', 'min:1'], 'option_ids.*' => ['integer', 'exists:poll_options,id']];

        $data = $request->validate($rules);

        $optionIds = $poll->type === 'single' ? [$data['option_id']] : $data['option_ids'];

        // Vérifie que toutes les options appartiennent bien à ce sondage
        // (évite qu'on vote sur les options d'un autre sondage).
        $validOptionIds = $poll->options->pluck('id');
        if (collect($optionIds)->diff($validOptionIds)->isNotEmpty()) {
            abort(422, 'Option invalide pour ce sondage.');
        }

        DB::transaction(function () use ($poll, $optionIds, $request) {
            // Retire un éventuel vote précédent de l'utilisateur sur ce
            // sondage (permet de changer son vote tant que le sondage est
            // ouvert), en décrémentant les compteurs concernés.
            $previousVotes = PollVote::whereIn('poll_option_id', $poll->options->pluck('id'))
                ->where('user_id', $request->user()->id)
                ->get();

            foreach ($previousVotes as $previousVote) {
                PollOption::where('id', $previousVote->poll_option_id)->decrement('vote_count');
                $previousVote->delete();
            }

            foreach ($optionIds as $optionId) {
                PollVote::create([
                    'poll_option_id' => $optionId,
                    'user_id' => $request->user()->id,
                    'voted_at' => now(),
                ]);
                PollOption::where('id', $optionId)->increment('vote_count');
            }
        });

        $poll->refresh()->load('options');

        broadcast(new PollVoted($poll));

        if ($request->wantsJson()) {
            $userVotes = collect($optionIds);

            return response()->json($this->formatPoll($poll, $userVotes));
        }

        return back()->with('success', 'Vote enregistré.');
    }

    public function destroy(Request $request, Poll $poll): RedirectResponse
    {
        Gate::authorize('delete', $poll);

        $poll->delete();

        return redirect()->route('polls.index')->with('success', 'Sondage supprimé.');
    }

    public function results(Poll $poll)
    {
        Gate::authorize('view', $poll);

        $poll->load('options');
        $total = $poll->options->sum('vote_count');

        return response()->json([
            'poll_id' => $poll->id,
            'total_votes' => $total,
            'options' => $poll->options->map(fn ($option) => [
                'id' => $option->id,
                'vote_count' => $option->vote_count,
                'percentage' => $total > 0 ? round(($option->vote_count / $total) * 100, 1) : 0,
            ]),
        ]);
    }

    public function exportCsv(Poll $poll)
    {
        Gate::authorize('exportResults', $poll);

        $poll->load('options');
        $total = $poll->options->sum('vote_count');
        $filename = 'sondage-' . $poll->id . '-resultats.csv';

        $callback = function () use ($poll, $total) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($handle, ['Sondage', $poll->title], ';');
            fputcsv($handle, ['Exporté le', now()->format('d/m/Y H:i')], ';');
            fputcsv($handle, ['Total de votes', $total], ';');
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Option', 'Votes', 'Pourcentage'], ';');

            foreach ($poll->options as $option) {
                $percentage = $total > 0 ? round(($option->vote_count / $total) * 100, 1) : 0;
                fputcsv($handle, [$option->option_text, $option->vote_count, $percentage . '%'], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function formatPoll(Poll $poll, $userVotedOptionIds): array
    {
        $total = $poll->options->sum('vote_count');
        $hasVoted = $poll->options->pluck('id')->intersect($userVotedOptionIds)->isNotEmpty();

        return [
            'id' => $poll->id,
            'title' => $poll->title,
            'description' => $poll->description,
            'type' => $poll->type,
            'is_anonymous' => $poll->is_anonymous,
            'expires_at' => $poll->expires_at?->toIso8601String(),
            'expires_at_human' => $poll->expires_at?->diffForHumans(),
            'is_expired' => $poll->isExpired(),
            'created_at_human' => $poll->created_at->diffForHumans(),
            'author' => [
                'id' => $poll->user->id,
                'name' => $poll->is_anonymous ? 'Anonyme' : $poll->user->name,
                'initial' => mb_strtoupper(mb_substr($poll->is_anonymous ? '?' : $poll->user->name, 0, 1)),
            ],
            'is_owner' => auth()->id() === $poll->user_id,
            'has_voted' => $hasVoted,
            'total_votes' => $total,
            'options' => $poll->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->option_text,
                'vote_count' => $option->vote_count,
                'percentage' => $total > 0 ? round(($option->vote_count / $total) * 100, 1) : 0,
                'voted_by_me' => $userVotedOptionIds->contains($option->id),
            ]),
        ];
    }
}
