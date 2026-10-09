<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Rider;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function recipients(Request $request): JsonResponse
    {
        [$user, $centerId] = $this->actor($request->user());

        return response()->json([
            'data' => $this->recipientUsers($user, $centerId)
                ->map(fn (User $recipient) => $this->formatUser($recipient))
                ->values(),
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        [$user, $centerId] = $this->actor($request->user());
        $conversations = Conversation::query()
            ->where('sorting_center_id', $centerId)
            ->whereHas('participants', fn (Builder $query) => $query->whereKey($user->id))
            ->with([
                'participants:id,name,role',
                'latestMessage.sender:id,name,role',
            ])
            ->withCount([
                'messages as unread_count' => fn (Builder $query) => $query
                    ->where('recipient_id', $user->id)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => $conversations->map(function (Conversation $conversation) use ($user): array {
                $otherParticipant = $conversation->participants
                    ->firstWhere('id', '!=', $user->id);

                return [
                    'id' => $conversation->id,
                    'recipient' => $otherParticipant ? $this->formatUser($otherParticipant) : null,
                    'latest_message' => $conversation->latestMessage ? [
                        'body' => $conversation->latestMessage->body,
                        'sender_id' => $conversation->latestMessage->sender_id,
                        'created_at' => $conversation->latestMessage->created_at,
                    ] : null,
                    'unread_count' => $conversation->unread_count,
                    'updated_at' => $conversation->updated_at,
                ];
            })->values(),
        ]);
    }

    public function show(Request $request, int $conversationId): JsonResponse
    {
        [$user, $centerId] = $this->actor($request->user());
        $conversation = $this->conversationFor($conversationId, $user, $centerId);

        $unreadMessages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at');
        $readAttributes = ['read_at' => now()];
        if (Schema::hasColumn('messages', 'is_read')) {
            $readAttributes['is_read'] = true;
        }
        $unreadMessages->update($readAttributes);

        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (Message $message) use ($user): array {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender' => $this->formatUser($message->sender),
                    'is_mine' => (int) $message->sender_id === (int) $user->id,
                    'body' => $message->body,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json(['data' => $messages]);
    }

    public function send(Request $request, ?int $conversationId = null): JsonResponse
    {
        [$user, $centerId] = $this->actor($request->user());
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $recipientId = null;
        if ($conversationId !== null) {
            $conversation = $this->conversationFor($conversationId, $user, $centerId);
            $recipient = $conversation->participants->firstWhere('id', '!=', $user->id);
            abort_unless($recipient, 404);
            $recipientId = $recipient->id;
        } else {
            $recipientId = $request->validate([
                'recipient_id' => ['required', 'integer', 'exists:users,id'],
            ])['recipient_id'];
            $recipient = $this->recipientUsers($user, $centerId)
                ->firstWhere('id', (int) $recipientId);
            if (!$recipient) {
                throw ValidationException::withMessages([
                    'recipient_id' => 'This account is not an eligible messaging recipient.',
                ]);
            }
        }

        $conversation = DB::transaction(function () use ($user, $recipientId, $centerId, $validated, $conversationId): Conversation {
            if ($conversationId !== null) {
                $conversation = Conversation::findOrFail($conversationId);
            } else {
                $conversation = Conversation::query()
                    ->where('sorting_center_id', $centerId)
                    ->whereHas('participants', fn (Builder $query) => $query->whereKey($user->id))
                    ->whereHas('participants', fn (Builder $query) => $query->whereKey($recipientId))
                    ->withCount('participants')
                    ->having('participants_count', 2)
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'sorting_center_id' => $centerId,
                        'created_by' => $user->id,
                    ]);
                    $conversation->participants()->attach([$user->id, $recipientId]);
                }
            }

            $messageAttributes = [
                'sender_id' => $user->id,
                'recipient_id' => $recipientId,
                'body' => $validated['body'],
            ];
            $legacyColumns = Schema::getColumnListing('messages');
            if (in_array('user_id', $legacyColumns, true)) {
                $messageAttributes['user_id'] = $recipientId;
            }
            if (in_array('subject', $legacyColumns, true)) {
                $messageAttributes['subject'] = 'Direct message';
            }
            if (in_array('message', $legacyColumns, true)) {
                $messageAttributes['message'] = $validated['body'];
            }
            if (in_array('is_read', $legacyColumns, true)) {
                $messageAttributes['is_read'] = false;
            }

            $conversation->messages()->create($messageAttributes);
            $conversation->touch();

            return $conversation;
        });

        return response()->json([
            'message' => 'Message sent.',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    private function actor(User $user): array
    {
        if ($user->role === 'sorting_center') {
            $center = SortingCenter::query()->where('user_id', $user->id)->firstOrFail();

            return [$user, $center->id, $center];
        }

        if ($user->role === 'rider') {
            $rider = Rider::query()
                ->where('user_id', $user->id)
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->firstOrFail();

            return [$user, $rider->sorting_center_id, $rider->sortingCenter];
        }

        abort(403, 'Messaging is only available to sorting centers and their riders.');
    }

    private function recipientUsers(User $user, int $centerId): Collection
    {
        if ($user->role === 'sorting_center') {
            return User::query()
                ->whereIn('id', Rider::query()
                    ->where('sorting_center_id', $centerId)
                    ->where('approval_status', 'approved')
                    ->where('is_active', true)
                    ->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        }

        $centerUser = SortingCenter::query()
            ->whereKey($centerId)
            ->firstOrFail()
            ->user()
            ->select('users.id', 'users.name', 'users.role')
            ->firstOrFail();

        $riderUsers = User::query()
            ->whereIn('id', Rider::query()
                ->where('sorting_center_id', $centerId)
                ->where('approval_status', 'approved')
                ->where('is_active', true)
                ->select('user_id'))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return collect([$centerUser])->concat($riderUsers);
    }

    private function conversationFor(int $conversationId, User $user, int $centerId): Conversation
    {
        return Conversation::query()
            ->whereKey($conversationId)
            ->where('sorting_center_id', $centerId)
            ->whereHas('participants', fn (Builder $query) => $query->whereKey($user->id))
            ->with('participants:id,name,role')
            ->firstOrFail();
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];
    }
}
