<?php

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Events\MessageReacted;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    protected const ALLOWED_REACTIONS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $conversations = $this->conversationsForSidebar($request->user()->id);

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json(['conversations' => $conversations]);
        }

        $activeConversation = null;

        if ($request->filled('c')) {
            $candidate = Conversation::find($request->integer('c'));
            if ($candidate && $candidate->hasParticipant($request->user()->id)) {
                $activeConversation = $this->loadConversation($candidate, $request->user()->id);
            }
        }

        return view('chat.index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $conversation = Conversation::findOrFail($id);

        Gate::authorize('view', $conversation);

        $data = $this->loadConversation($conversation, $request->user()->id);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('chat.index', [
            'conversations' => $this->conversationsForSidebar($request->user()->id),
            'activeConversation' => $data,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'content' => ['nullable', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:messages,id'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt'],
        ]);

        if (empty($data['content']) && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Message vide.'], 422);
        }

        $conversation = Conversation::findOrFail($data['conversation_id']);

        Gate::authorize('send', $conversation);

        $attributes = [
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'] ?? '',
            'type' => 'text',
            'parent_id' => $data['parent_id'] ?? null,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments', 'public');
            $isImage = str_starts_with($file->getMimeType(), 'image/');

            $attributes['type'] = $isImage ? 'image' : 'file';
            $attributes['attachment_path'] = $path;
            $attributes['attachment_name'] = $file->getClientOriginalName();
            $attributes['attachment_mime'] = $file->getMimeType();
            $attributes['attachment_size'] = $file->getSize();
        }

        $message = Message::create($attributes);

        $conversation->touch();

        $conversation->participants()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now(),
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message->load('user')),
        ]);
    }

    public function toggleReaction(Request $request, Message $message)
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_REACTIONS)],
        ]);

        Gate::authorize('view', $message->conversation);

        $existing = $message->reactions()
            ->where('user_id', $request->user()->id)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $message->reactions()->create([
                'user_id' => $request->user()->id,
                'emoji' => $data['emoji'],
            ]);
        }

        $message->load('reactions');

        broadcast(new MessageReacted($message))->toOthers();

        return response()->json(['reactions' => $message->reactionSummary($request->user()->id)]);
    }

    public function typing(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'is_typing' => ['boolean'],
        ]);

        $conversation = Conversation::findOrFail($data['conversation_id']);
        Gate::authorize('view', $conversation);

        broadcast(new UserTyping(
            $conversation->id,
            $request->user(),
            $request->boolean('is_typing', true)
        ))->toOthers();

        return response()->noContent();
    }

    public function createConversation(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:private,group'],
            'participant_id' => ['required_if:type,private', 'nullable', 'integer', 'exists:users,id'],
            'participants' => ['required_if:type,group', 'nullable', 'array', 'min:1'],
            'participants.*' => ['integer', 'exists:users,id'],
            'name' => ['required_if:type,group', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()->id;

        if ($data['type'] === 'private') {
            if ((int) $data['participant_id'] === $userId) {
                return response()->json(['message' => "Tu ne peux pas démarrer une conversation avec toi-même."], 422);
            }

            $existing = Conversation::query()
                ->where('type', 'private')
                ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $data['participant_id']))
                ->withCount('participants')
                ->having('participants_count', 2)
                ->first();

            if ($existing) {
                return response()->json(['conversation_id' => $existing->id]);
            }

            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $userId,
            ]);

            $conversation->participants()->attach([
                $userId => ['role' => 'member', 'joined_at' => now()],
                $data['participant_id'] => ['role' => 'member', 'joined_at' => now()],
            ]);

            return response()->json(['conversation_id' => $conversation->id]);
        }

        Gate::authorize('createGroup', Conversation::class);

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
        ]);

        $participantIds = array_unique(array_merge($data['participants'], [$userId]));

        $attach = [];
        foreach ($participantIds as $participantId) {
            $attach[$participantId] = [
                'role' => $participantId === $userId ? 'admin' : 'member',
                'joined_at' => now(),
            ];
        }
        $conversation->participants()->attach($attach);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function searchUsers(Request $request)
    {
        $term = trim((string) $request->get('q', ''));

        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    // ==================== Helpers internes ====================

    protected function conversationsForSidebar(int $userId)
    {
        return Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with(['participants' => fn ($q) => $q->where('user_id', '!=', $userId)])
            ->with('lastMessage.user')
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest('created_at')
                    ->limit(1)
            )
            ->get()
            ->map(function (Conversation $conversation) use ($userId) {
                $pivot = $conversation->participants()->where('user_id', $userId)->first()?->pivot;
                $lastReadAt = $pivot?->last_read_at;

                $unread = Message::where('conversation_id', $conversation->id)
                    ->where('user_id', '!=', $userId)
                    ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
                    ->count();

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'display_name' => $conversation->type === 'group'
                        ? $conversation->name
                        : optional($conversation->participants->first())->name ?? 'Utilisateur supprimé',
                    'avatar_initial' => mb_strtoupper(mb_substr($conversation->type === 'group'
                        ? ($conversation->name ?? 'G')
                        : (optional($conversation->participants->first())->name ?? '?'), 0, 1)),
                    'last_message' => $conversation->lastMessage?->content,
                    'last_message_at' => $conversation->lastMessage?->created_at?->diffForHumans(),
                    'unread_count' => $unread,
                ];
            });
    }

    protected function loadConversation(Conversation $conversation, int $userId): array
    {
        $conversation->load(['participants', 'creator']);

        $messages = $conversation->messages()
            ->with(['user', 'reactions'])
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $now = now();

        $conversation->participants()->updateExistingPivot($userId, [
            'last_read_at' => $now,
        ]);

        broadcast(new ConversationRead($conversation->id, $userId, $now->toIso8601String()))->toOthers();

        $otherParticipant = $conversation->type === 'private'
            ? $conversation->participants->firstWhere('id', '!=', $userId)
            : null;

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->type === 'group' ? $conversation->name : $otherParticipant?->name,
            'description' => $conversation->description,
            'participants' => $conversation->participants->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'role' => $p->pivot->role,
                'last_read_at' => $p->pivot->last_read_at,
            ]),
            'messages' => $messages->map(fn ($m) => $this->formatMessage($m, $userId)),
        ];
    }

    protected function formatMessage(Message $message, ?int $currentUserId = null): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'content' => $message->content,
            'type' => $message->type,
            'parent_id' => $message->parent_id,
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_human' => $message->created_at->format('H:i'),
            'attachment' => $message->attachment_path ? [
                'url' => Storage::disk('public')->url($message->attachment_path),
                'name' => $message->attachment_name,
                'mime' => $message->attachment_mime,
                'size' => $message->attachment_size,
                'is_image' => str_starts_with((string) $message->attachment_mime, 'image/'),
            ] : null,
            'reactions' => $message->relationLoaded('reactions') ? $message->reactionSummary($currentUserId) : [],
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'initial' => mb_strtoupper(mb_substr($message->user->name, 0, 1)),
            ],
        ];
    }
}
