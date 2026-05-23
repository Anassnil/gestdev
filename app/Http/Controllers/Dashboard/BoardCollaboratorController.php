<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardCollaborator;
use App\Models\User;
use Illuminate\Http\Request;

class BoardCollaboratorController extends Controller
{
    /**
     * Search users to invite (AJAX, returns JSON).
     */
    public function search(Board $board, Request $request)
    {
        $this->requireOwner($board, $request);

        $q = trim($request->input('q', ''));

        // Exclude already-added collaborators and the owner
        $excluded = $board->collaborators()->pluck('user_id')->push($board->user_id);

        $users = User::where('id', '!=', $request->user()->id)
            ->whereNotIn('id', $excluded)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar_path']);

        return response()->json(['users' => $users->map(fn($u) => [
            'id'        => $u->id,
            'name'      => $u->name,
            'email'     => $u->email,
            'initials'  => $u->initials,
            'avatar_url' => $u->avatar_url,
        ])]);
    }

    /**
     * Invite a user as collaborator.
     */
    public function invite(Board $board, Request $request)
    {
        $this->requireOwner($board, $request);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'in:viewer,editor'],
        ]);

        // Prevent inviting the owner themselves
        if ($data['user_id'] == $board->user_id) {
            return response()->json(['error' => 'Cannot invite the board owner.'], 422);
        }

        BoardCollaborator::updateOrCreate(
            ['board_id' => $board->id, 'user_id' => $data['user_id']],
            ['role' => $data['role'], 'invited_by' => $request->user()->id]
        );

        $user = User::findOrFail($data['user_id']);

        return response()->json([
            'ok'       => true,
            'id'       => $user->id,
            'name'     => $user->name,
            'initials' => $user->initials,
            'avatar'   => $user->avatar_url,
            'role'     => $data['role'],
        ]);
    }

    /**
     * Update a collaborator's role.
     */
    public function updateRole(Board $board, Request $request)
    {
        $this->requireOwner($board, $request);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'in:viewer,editor'],
        ]);

        $collab = BoardCollaborator::where('board_id', $board->id)
            ->where('user_id', $data['user_id'])
            ->firstOrFail();

        $collab->update(['role' => $data['role']]);

        return response()->json(['ok' => true]);
    }

    /**
     * Remove a collaborator from the board.
     * Owner can remove anyone; collaborators can remove themselves.
     */
    public function remove(Board $board, Request $request)
    {
        $me = $request->user();
        $targetId = (int) $request->input('user_id', $me->id);

        // Must be owner to remove others; anyone can remove themselves
        if ($targetId !== $me->id && $board->user_id !== $me->id) {
            abort(403);
        }

        BoardCollaborator::where('board_id', $board->id)
            ->where('user_id', $targetId)
            ->delete();

        // If user removed themselves, redirect to planning index
        if ($targetId === $me->id) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'self' => true]);
            }
            return redirect()->route('dashboard.planning.index')
                ->with('success', 'You left the board.');
        }

        return response()->json(['ok' => true]);
    }

    /**
     * List collaborators for the sharing modal.
     */
    public function list(Board $board, Request $request)
    {
        $this->requireOwner($board, $request);

        $collabs = $board->collaborators()->with('user')->get()->map(fn($c) => [
            'id'        => $c->user->id,
            'name'      => $c->user->name,
            'email'     => $c->user->email,
            'initials'  => $c->user->initials,
            'avatar_url' => $c->user->avatar_url,
            'role'      => $c->role,
        ]);

        return response()->json(['collaborators' => $collabs]);
    }

    private function requireOwner(Board $board, Request $request): void
    {
        if ($board->user_id !== $request->user()->id) {
            abort(403, 'Only the board owner can manage collaborators.');
        }
    }
}
