<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Associate;
use App\Models\Board;
use App\Models\GroupChat;
use App\Models\GroupChatMember;
use App\Models\GroupChatMessage;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PeopleController extends Controller
{
    /**
     * Public directory — all users except self.
     */
    public function index(Request $request)
    {
        $q = $request->input('q', '');
        $scope = $request->input('scope', 'all');
        $me = $request->user();

        $associations = Associate::where('user_id', $me->id)
            ->get(['associate_user_id', 'relationship_type'])
            ->keyBy('associate_user_id');

        $associateIds = $associations->keys()->values();

        $people = User::where('id', '!=', $me->id)
            ->when($scope === 'associates', fn ($query) => $query->whereIn('id', $associateIds))
            ->when($q, fn ($query) => $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $associates = User::whereIn('id', $associations->keys())
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($associations) {
                $user->relationship_type = $associations[$user->id]->relationship_type ?? 'associate';
                return $user;
            });

        $allMembersCount = User::where('id', '!=', $me->id)->count();
        $associatesCount = $associateIds->count();

        return view('dashboard.people.index', compact('people', 'q', 'associates', 'associations', 'scope', 'allMembersCount', 'associatesCount'));
    }

    /**
     * View a single user's public profile.
     */
    public function show(User $user)
    {
        $me = auth()->user();

        // Last 3 shared messages for preview
        $recentMessages = Message::where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->latest()->take(3)->get()->reverse();

        // ── AI profile insights ────────────────────────────────────
        $myStack      = collect($me->tech_stack ?? []);
        $theirStack   = collect($user->tech_stack ?? []);
        $sharedSkills = $myStack->intersect($theirStack)->values();

        $totalUnion       = $myStack->merge($theirStack)->unique()->count();
        $skillScore       = $totalUnion > 0 ? round(($sharedSkills->count() / $totalUnion) * 70) : 0;

        $totalMessages = Message::where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->count();

        $convoBonus       = $totalMessages > 20 ? 20 : ($totalMessages > 5 ? 15 : ($totalMessages > 0 ? 10 : 0));
        $compatibilityScore = min(100, $skillScore + $convoBonus + ($user->bio ? 5 : 0) + ($user->position ? 5 : 0));

        $completenessFields  = ['name', 'bio', 'position', 'tech_stack', 'experience', 'education', 'github_url', 'linkedin_url'];
        $filled              = collect($completenessFields)->filter(fn($f) => !empty($user->$f))->count();
        $profileCompleteness = round(($filled / count($completenessFields)) * 100);

        return view('dashboard.people.show', compact(
            'user', 'recentMessages',
            'sharedSkills', 'compatibilityScore', 'totalMessages', 'profileCompleteness'
        ));
    }

    // ── Chat ────────────────────────────────────────────────────────

    /**
     * Inbox — list all conversations the current user is part of.
     */
    public function inbox(Request $request)
    {
        $me = $request->user();

        // Latest message per conversation partner, with unread counts
        $conversations = DB::table('messages')
            ->select(DB::raw('
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                MAX(id) AS last_message_id
            '))
            ->addBinding($me->id, 'select')
            ->where('sender_id', $me->id)
            ->orWhere('receiver_id', $me->id)
            ->groupBy('partner_id')
            ->orderByDesc('last_message_id')
            ->get();

        $partnerIds = $conversations->pluck('partner_id');
        $partners = User::whereIn('id', $partnerIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn('id', $conversations->pluck('last_message_id'))
            ->get()->keyBy('id');

        $unreadCounts = Message::where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->select('sender_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sender_id')
            ->pluck('cnt', 'sender_id');

        $threads = $conversations->map(function ($row) use ($partners, $lastMessages, $unreadCounts) {
            return (object) [
                'partner'      => $partners[$row->partner_id] ?? null,
                'lastMessage'  => $lastMessages[$row->last_message_id] ?? null,
                'unread'       => $unreadCounts[$row->partner_id] ?? 0,
            ];
        })->filter(fn ($t) => $t->partner !== null);

        $groupThreads = GroupChat::with(['board:id,name'])
            ->whereHas('members', fn ($q) => $q->where('user_id', $me->id))
            ->withCount('members')
            ->get()
            ->map(function (GroupChat $chat) use ($me) {
                $member = $chat->members()->where('user_id', $me->id)->first();
                $lastMessage = GroupChatMessage::where('group_chat_id', $chat->id)->latest('id')->first();
                $lastReadId = $member?->last_read_message_id ?? 0;
                $unread = GroupChatMessage::where('group_chat_id', $chat->id)
                    ->where('id', '>', $lastReadId)
                    ->where('sender_id', '!=', $me->id)
                    ->count();

                return (object) [
                    'chat' => $chat,
                    'lastMessage' => $lastMessage,
                    'unread' => $unread,
                ];
            })
            ->sortByDesc(fn ($t) => $t->lastMessage?->id ?? 0)
            ->values();

        $totalUnread = $me->unreadMessageCount() + $groupThreads->sum('unread');

        $associateRows = Associate::where('user_id', $me->id)
            ->get(['associate_user_id', 'relationship_type'])
            ->keyBy('associate_user_id');

        $associateOptions = User::whereIn('id', $associateRows->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($associateRows) {
                $user->relationship_type = $associateRows[$user->id]->relationship_type ?? 'associate';
                return $user;
            });

        $boardIds = Board::where('user_id', $me->id)->pluck('id')
            ->merge(DB::table('board_collaborators')->where('user_id', $me->id)->pluck('board_id'))
            ->unique()
            ->values();

        $boards = Board::whereIn('id', $boardIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard.people.inbox', compact('threads', 'groupThreads', 'totalUnread', 'associateOptions', 'boards'));
    }

    /**
     * JSON inbox for the floating chat widget.
     */
    public function inboxJson(Request $request)
    {
        $me = $request->user();

        $conversations = DB::table('messages')
            ->select(DB::raw('
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                MAX(id) AS last_message_id
            '))
            ->addBinding($me->id, 'select')
            ->where('sender_id', $me->id)
            ->orWhere('receiver_id', $me->id)
            ->groupBy('partner_id')
            ->orderByDesc('last_message_id')
            ->limit(30)
            ->get();

        $partnerIds   = $conversations->pluck('partner_id');
        $partners     = User::whereIn('id', $partnerIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn('id', $conversations->pluck('last_message_id'))->get()->keyBy('id');
        $unreadCounts = Message::where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->select('sender_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sender_id')
            ->pluck('cnt', 'sender_id');

        $threads = $conversations->map(function ($row) use ($partners, $lastMessages, $unreadCounts, $me) {
            $partner = $partners[$row->partner_id] ?? null;
            if (!$partner) return null;
            $last = $lastMessages[$row->last_message_id] ?? null;
            $preview = $last ? (($last->sender_id === $me->id ? 'You: ' : '') . $last->body) : '';
            return [
                'id'      => $partner->id,
                'name'    => $partner->name,
                'initials'=> $partner->initials,
                'avatar'  => $partner->avatar_url,
                'preview' => mb_strimwidth($preview, 0, 50, '…'),
                'ago'     => $last ? $last->created_at->diffForHumans(null, true, true) : '',
                'unread'  => (int) ($unreadCounts[$row->partner_id] ?? 0),
            ];
        })->filter()->values();

        return response()->json(['threads' => $threads]);
    }

    /**
     * Conversation with a specific user. Marks all incoming messages as read.
     */
    public function chat(User $user, Request $request)
    {
        $me = $request->user();

        Cache::put("chat:last_seen:{$me->id}", now()->timestamp, now()->addMinutes(10));

        // Mark their messages to me as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::with(['replyTo', 'reactions'])
            ->where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->orderBy('created_at')->get();

        // Build sidebar threads (direct conversations)
        $conversations = DB::table('messages')
            ->select(DB::raw('
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                MAX(id) AS last_message_id
            '))
            ->addBinding($me->id, 'select')
            ->where('sender_id', $me->id)
            ->orWhere('receiver_id', $me->id)
            ->groupBy('partner_id')
            ->orderByDesc('last_message_id')
            ->get();

        $partnerIds   = $conversations->pluck('partner_id');
        $partners     = User::whereIn('id', $partnerIds)->get()->keyBy('id');
        $lastMessages = Message::whereIn('id', $conversations->pluck('last_message_id'))->get()->keyBy('id');
        $unreadCounts = Message::where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->select('sender_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sender_id')
            ->pluck('cnt', 'sender_id');

        $threads = $conversations->map(function ($row) use ($partners, $lastMessages, $unreadCounts, $me) {
            $partner = $partners[$row->partner_id] ?? null;
            if (!$partner) return null;
            $last    = $lastMessages[$row->last_message_id] ?? null;
            $preview = '';
            if ($last) {
                $preview = ($last->sender_id === $me->id ? 'You: ' : '') .
                    ($last->attachment_name ? '📎 '.$last->attachment_name : $last->body);
            }
            return (object)[
                'id'      => $partner->id,
                'name'    => $partner->name,
                'initials'=> $partner->initials,
                'avatar'  => $partner->avatar_url,
                'preview' => mb_strimwidth($preview, 0, 42, '…'),
                'unread'  => (int) ($unreadCounts[$row->partner_id] ?? 0),
                'url'     => route('dashboard.people.chat', $partner),
                'last_time' => $last?->created_at?->format('H:i'),
            ];
        })->filter()->values();

        // Group chats for sidebar
        $groupChats = GroupChat::whereHas('members', fn ($q) => $q->where('user_id', $me->id))
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->get()
            ->map(function (GroupChat $gc) use ($me) {
                $last = $gc->messages->first();
                return (object)[
                    'id'      => $gc->id,
                    'name'    => $gc->name,
                    'preview' => $last ? mb_strimwidth($last->body ?: '📎 Attachment', 0, 42, '…') : 'No messages yet',
                    'url'     => route('dashboard.people.group.chat', $gc),
                    'last_time' => $last?->created_at?->format('H:i'),
                ];
            });

        $sharedFiles = Message::where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->whereNotNull('attachment_path')
            ->latest()
            ->take(40)
            ->get(['id', 'attachment_name', 'attachment_mime', 'attachment_path', 'created_at', 'sender_id'])
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'name' => $m->attachment_name,
                'mime' => $m->attachment_mime,
                'url' => $m->attachment_url,
                'is_image' => $m->is_image,
                'created_at' => $m->created_at->toIso8601String(),
                'mine' => $m->sender_id === $me->id,
            ]);

        $partnerOnline = (bool) Cache::get("chat:last_seen:{$user->id}")
            && now()->timestamp - ((int) Cache::get("chat:last_seen:{$user->id}")) <= 120;

        // Ensure current user is in threads even if no prior message (won't be, but safe)
        return view('dashboard.people.chat', compact('user', 'messages', 'threads', 'groupChats', 'sharedFiles', 'partnerOnline'));
    }

    /**
     * Send a message. Returns JSON for AJAX or redirects for non-JS.
     */
    public function sendMessage(User $user, Request $request)
    {
        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:2000'],
            'reply_to_id'=> ['nullable', 'integer', 'exists:messages,id'],
            'attachment' => ['nullable', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,mp4,mp3'],
        ]);

        if (empty($data['body']) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment required.'], 422);
        }

        $me = $request->user();

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }

        $replyToId = $data['reply_to_id'] ?? null;
        if ($replyToId) {
            $validReply = Message::where('id', $replyToId)
                ->where(function ($q) use ($me, $user) {
                    $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($me, $user) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
                })->exists();

            if (!$validReply) {
                return response()->json(['error' => 'Invalid reply target.'], 422);
            }
        }

        $message = Message::create([
            'sender_id'       => $me->id,
            'receiver_id'     => $user->id,
            'body'            => $data['body'] ?? '',
            'reply_to_id'     => $replyToId,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);

        Cache::put("chat:last_seen:{$me->id}", now()->timestamp, now()->addMinutes(10));
        Cache::put("chat:typing:{$me->id}:{$user->id}", false, now()->addSeconds(15));

        $message->load(['replyTo', 'reactions']);

        if ($request->wantsJson()) {
            return response()->json($this->formatDirectMessagePayload($message, $me->id));
        }

        return redirect()->route('dashboard.people.chat', $user);
    }

    /**
     * Polling endpoint: fetch messages newer than a given ID (for AJAX chat refresh).
     */
    public function poll(User $user, Request $request)
    {
        $me = $request->user();
        $after = (int) $request->input('after', 0);

        Cache::put("chat:last_seen:{$me->id}", now()->timestamp, now()->addMinutes(10));

        // Mark incoming as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::with(['replyTo', 'reactions'])
            ->where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })
        ->where('id', '>', $after)
        ->orderBy('created_at')
        ->get()
        ->map(fn ($m) => $this->formatDirectMessagePayload($m, $me->id));

        $partnerTyping = (bool) Cache::get("chat:typing:{$user->id}:{$me->id}", false);
        $partnerLastSeen = (int) Cache::get("chat:last_seen:{$user->id}", 0);
        $partnerOnline = $partnerLastSeen > 0 && (now()->timestamp - $partnerLastSeen <= 120);

        return response()->json([
            'messages' => $messages,
            'typing' => $partnerTyping,
            'partner_online' => $partnerOnline,
        ]);
    }

    /**
     * Unread badge count for the nav (quick AJAX poll).
     */
    public function unreadCount(Request $request)
    {
        $me = $request->user();

        $groupUnread = GroupChat::whereHas('members', fn ($q) => $q->where('user_id', $me->id))
            ->get()
            ->sum(function (GroupChat $chat) use ($me) {
                $member = $chat->members()->where('user_id', $me->id)->first();
                $lastReadId = $member?->last_read_message_id ?? 0;
                return GroupChatMessage::where('group_chat_id', $chat->id)
                    ->where('id', '>', $lastReadId)
                    ->where('sender_id', '!=', $me->id)
                    ->count();
            });

        return response()->json(['count' => $me->unreadMessageCount() + $groupUnread]);
    }

    public function addAssociate(User $user, Request $request)
    {
        $me = $request->user();
        if ($me->id === $user->id) {
            return back()->with('error', 'You cannot add yourself as an associate.');
        }

        $data = $request->validate([
            'relationship_type' => ['nullable', 'string', 'in:associate,friend,partner'],
        ]);

        Associate::updateOrCreate(
            ['user_id' => $me->id, 'associate_user_id' => $user->id],
            ['relationship_type' => $data['relationship_type'] ?? 'associate']
        );

        return back()->with('success', 'Associate saved.');
    }

    public function removeAssociate(User $user, Request $request)
    {
        $me = $request->user();

        Associate::where('user_id', $me->id)
            ->where('associate_user_id', $user->id)
            ->delete();

        return back()->with('success', 'Associate removed.');
    }

    public function createGroupChat(Request $request)
    {
        $me = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if (!empty($data['board_id'])) {
            $canAccess = Board::where('id', $data['board_id'])
                ->where(function ($q) use ($me) {
                    $q->where('user_id', $me->id)
                        ->orWhereExists(function ($sq) use ($me) {
                            $sq->select(DB::raw(1))
                                ->from('board_collaborators')
                                ->whereColumn('board_collaborators.board_id', 'boards.id')
                                ->where('board_collaborators.user_id', $me->id);
                        });
                })
                ->exists();

            if (!$canAccess) {
                return back()->with('error', 'You cannot create a chat for this project.');
            }
        }

        $chat = GroupChat::create([
            'name' => $data['name'],
            'board_id' => $data['board_id'] ?? null,
            'created_by' => $me->id,
        ]);

        $memberIds = collect($data['member_ids'] ?? [])
            ->push($me->id)
            ->unique()
            ->values();

        foreach ($memberIds as $memberId) {
            GroupChatMember::create([
                'group_chat_id' => $chat->id,
                'user_id' => $memberId,
                'role' => (int) $memberId === $me->id ? 'owner' : 'member',
            ]);
        }

        return redirect()->route('dashboard.people.group.chat', $chat);
    }

    public function groupChat(GroupChat $groupChat, Request $request)
    {
        $me = $request->user();
        $membership = GroupChatMember::where('group_chat_id', $groupChat->id)
            ->where('user_id', $me->id)
            ->first();

        abort_unless($membership, 403);

        $messages = GroupChatMessage::with('sender')
            ->where('group_chat_id', $groupChat->id)
            ->orderBy('created_at')
            ->get();

        $lastMessageId = $messages->max('id');
        if ($lastMessageId) {
            $membership->update(['last_read_message_id' => $lastMessageId]);
        }

        $members = $groupChat->members()->with('user')->get();

        return view('dashboard.people.group-chat', compact('groupChat', 'messages', 'members'));
    }

    public function sendGroupMessage(GroupChat $groupChat, Request $request)
    {
        $me = $request->user();
        $membership = GroupChatMember::where('group_chat_id', $groupChat->id)
            ->where('user_id', $me->id)
            ->first();

        abort_unless($membership, 403);

        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,mp4,mp3'],
        ]);

        if (empty($data['body']) && !$request->hasFile('attachment')) {
            return response()->json(['error' => 'Message or attachment required.'], 422);
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMime = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('chat-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
            $attachmentMime = $file->getMimeType();
        }

        $message = GroupChatMessage::create([
            'group_chat_id'   => $groupChat->id,
            'sender_id'       => $me->id,
            'body'            => $data['body'] ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
        ]);

        $membership->update(['last_read_message_id' => $message->id]);

        return response()->json([
            'id'              => $message->id,
            'body'            => $message->body,
            'mine'            => true,
            'sender_name'     => $me->name,
            'created_at'      => $message->created_at->toIso8601String(),
            'attachment_url'  => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
        ]);
    }

    public function pollGroupChat(GroupChat $groupChat, Request $request)
    {
        $me = $request->user();
        $membership = GroupChatMember::where('group_chat_id', $groupChat->id)
            ->where('user_id', $me->id)
            ->first();

        abort_unless($membership, 403);

        $after = (int) $request->input('after', 0);

        $messages = GroupChatMessage::with('sender')
            ->where('group_chat_id', $groupChat->id)
            ->where('id', '>', $after)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'              => $m->id,
                'body'            => $m->body,
                'mine'            => $m->sender_id === $me->id,
                'sender_name'     => $m->sender?->name ?? 'Member',
                'created_at'      => $m->created_at->toIso8601String(),
                'attachment_url'  => $m->attachment_url,
                'attachment_name' => $m->attachment_name,
                'attachment_mime' => $m->attachment_mime,
            ]);

        $lastMessageId = GroupChatMessage::where('group_chat_id', $groupChat->id)->max('id');
        if ($lastMessageId) {
            $membership->update(['last_read_message_id' => $lastMessageId]);
        }

        return response()->json(['messages' => $messages]);
    }

    public function typing(User $user, Request $request)
    {
        $me = $request->user();
        $data = $request->validate([
            'typing' => ['required', 'boolean'],
        ]);

        Cache::put("chat:last_seen:{$me->id}", now()->timestamp, now()->addMinutes(10));
        Cache::put("chat:typing:{$me->id}:{$user->id}", $data['typing'], now()->addSeconds(12));

        return response()->json(['ok' => true]);
    }

    public function searchMessages(User $user, Request $request)
    {
        $me = $request->user();
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['messages' => []]);
        }

        $messages = Message::with(['replyTo', 'reactions'])
            ->where(function ($qb) use ($me, $user) {
                $qb->where('sender_id', $me->id)->where('receiver_id', $user->id);
            })->orWhere(function ($qb) use ($me, $user) {
                $qb->where('sender_id', $user->id)->where('receiver_id', $me->id);
            })
            ->where('body', 'like', "%{$q}%")
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Message $m) => $this->formatDirectMessagePayload($m, $me->id));

        return response()->json(['messages' => $messages]);
    }

    public function sharedMedia(User $user, Request $request)
    {
        $me = $request->user();

        $files = Message::where(function ($q) use ($me, $user) {
            $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
        })->whereNotNull('attachment_path')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'name' => $m->attachment_name,
                'mime' => $m->attachment_mime,
                'url' => $m->attachment_url,
                'is_image' => $m->is_image,
                'created_at' => $m->created_at->toIso8601String(),
                'mine' => $m->sender_id === $me->id,
            ]);

        return response()->json(['files' => $files]);
    }

    public function editMessage(User $user, Message $message, Request $request)
    {
        $me = $request->user();
        abort_unless($message->sender_id === $me->id, 403);
        abort_unless($message->receiver_id === $user->id || $message->sender_id === $user->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        $message->load(['replyTo', 'reactions']);
        return response()->json($this->formatDirectMessagePayload($message, $me->id));
    }

    public function deleteMessage(User $user, Message $message, Request $request)
    {
        $me = $request->user();
        abort_unless($message->sender_id === $me->id, 403);
        abort_unless($message->receiver_id === $user->id || $message->sender_id === $user->id, 403);

        $message->delete();

        return response()->json(['ok' => true, 'id' => $message->id]);
    }

    public function toggleReaction(User $user, Message $message, Request $request)
    {
        $me = $request->user();
        abort_unless(
            ($message->sender_id === $me->id && $message->receiver_id === $user->id)
            || ($message->sender_id === $user->id && $message->receiver_id === $me->id),
            403
        );

        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:24'],
        ]);

        $reaction = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $me->id)
            ->where('emoji', $data['emoji'])
            ->first();

        if ($reaction) {
            $reaction->delete();
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $me->id,
                'emoji' => $data['emoji'],
            ]);
        }

        $message->load('reactions');
        return response()->json([
            'id' => $message->id,
            'reactions' => $this->formatReactions($message, $me->id),
        ]);
    }

    public function forwardMessage(User $user, Message $message, Request $request)
    {
        $me = $request->user();
        abort_unless(
            ($message->sender_id === $me->id && $message->receiver_id === $user->id)
            || ($message->sender_id === $user->id && $message->receiver_id === $me->id),
            403
        );

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        abort_if((int) $data['to_user_id'] === $me->id, 422, 'Cannot forward to yourself.');

        Message::create([
            'sender_id' => $me->id,
            'receiver_id' => $data['to_user_id'],
            'body' => trim('Fwd: '.$message->body),
            'attachment_path' => $message->attachment_path,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
        ]);

        return response()->json(['ok' => true]);
    }

    private function formatDirectMessagePayload(Message $message, int $myId): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => $message->sender_id === $myId,
            'created_at' => $message->created_at->toIso8601String(),
            'attachment_url' => $message->attachment_url,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'body' => $message->replyTo->body,
            ] : null,
            'reactions' => $this->formatReactions($message, $myId),
        ];
    }

    private function formatReactions(Message $message, int $myId): array
    {
        $grouped = $message->reactions
            ->groupBy('emoji')
            ->map(function ($rows, $emoji) use ($myId) {
                return [
                    'emoji' => $emoji,
                    'count' => $rows->count(),
                    'mine' => $rows->contains(fn (MessageReaction $r) => $r->user_id === $myId),
                ];
            })
            ->values();

        return $grouped->all();
    }
}
