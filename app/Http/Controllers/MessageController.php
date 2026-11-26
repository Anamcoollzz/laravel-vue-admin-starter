<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * Display the messaging workspace with contact list, unread counts, and active conversation.
     */
    public function index(Request $request): Response
    {
        /** @var User $authUser */
        $authUser = $request->user();
        $search = $request->string('search')->trim()->value();
        $selectedUserId = $request->integer('user') ?: null;

        $otherUsers = User::query()
            ->where('id', '!=', $authUser->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $unreadCounts = Message::query()
            ->selectRaw('sender_id, COUNT(*) as unread_count')
            ->where('receiver_id', $authUser->id)
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id');

        $recentMessages = Message::query()
            ->where(function ($query) use ($authUser): void {
                $query->where('sender_id', $authUser->id)
                    ->orWhere('receiver_id', $authUser->id);
            })
            ->latest('id')
            ->get();

        $lastMessageByPeer = [];
        foreach ($recentMessages as $msg) {
            $peerId = $msg->sender_id === $authUser->id ? $msg->receiver_id : $msg->sender_id;

            if (! isset($lastMessageByPeer[$peerId])) {
                $lastMessageByPeer[$peerId] = $msg;
            }
        }

        $contacts = $otherUsers
            ->map(function (User $peer) use ($unreadCounts, $lastMessageByPeer): array {
                /** @var Message|null $last */
                $last = $lastMessageByPeer[$peer->id] ?? null;

                return [
                    'id' => $peer->id,
                    'name' => $peer->name,
                    'email' => $peer->email,
                    'avatar' => $peer->avatar,
                    'unread_count' => (int) ($unreadCounts[$peer->id] ?? 0),
                    'last_message' => $last?->body,
                    'last_message_at' => $last?->created_at?->format('H:i'),
                    'last_timestamp' => $last?->created_at?->getTimestamp() ?? 0,
                ];
            })
            ->sortByDesc('last_timestamp')
            ->values()
            ->all();

        $activeUser = null;
        $messages = [];

        if ($selectedUserId !== null) {
            $peer = User::query()
                ->where('id', '!=', $authUser->id)
                ->find($selectedUserId);

            if ($peer !== null) {
                Message::query()
                    ->where('sender_id', $peer->id)
                    ->where('receiver_id', $authUser->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

                $activeUser = [
                    'id' => $peer->id,
                    'name' => $peer->name,
                    'email' => $peer->email,
                    'avatar' => $peer->avatar,
                ];

                $messages = Message::query()
                    ->where(function ($query) use ($authUser, $peer): void {
                        $query->where(function ($q) use ($authUser, $peer): void {
                            $q->where('sender_id', $authUser->id)
                                ->where('receiver_id', $peer->id);
                        })->orWhere(function ($q) use ($authUser, $peer): void {
                            $q->where('sender_id', $peer->id)
                                ->where('receiver_id', $authUser->id);
                        });
                    })
                    ->orderBy('id')
                    ->get()
                    ->map(fn (Message $message): array => [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'receiver_id' => $message->receiver_id,
                        'body' => $message->body,
                        'is_mine' => $message->sender_id === $authUser->id,
                        'is_read' => $message->read_at !== null,
                        'created_at' => $message->created_at?->format('Y-m-d H:i'),
                    ])
                    ->all();
            }
        }

        return Inertia::render('messages/Index', [
            'contacts' => $contacts,
            'activeUser' => $activeUser,
            'messages' => $messages,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Send a new direct message from the authenticated user to the selected recipient.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $validated = $request->validate([
            'receiver_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id'),
                Rule::notIn([$authUser->id]),
            ],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        Message::query()->create([
            'sender_id' => $authUser->id,
            'receiver_id' => (int) $validated['receiver_id'],
            'body' => trim($validated['body']),
        ]);

        return back();
    }
}
