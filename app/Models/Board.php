<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = ['name','description','user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class)->orderBy('start_at');
    }

    public function releases()
    {
        return $this->hasMany(\App\Models\Release::class)->orderBy('position');
    }

    public function requirements()
    {
        return $this->hasMany(\App\Models\Requirement::class)->orderBy('position');
    }

    // ── Collaboration ──────────────────────────────────────────────

    public function collaborators()
    {
        return $this->hasMany(BoardCollaborator::class);
    }

    public function collaboratorUsers()
    {
        return $this->belongsToMany(User::class, 'board_collaborators')
                    ->withPivot('role', 'invited_by')
                    ->withTimestamps();
    }

    /** True if the given user is the owner. */
    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /** True if the given user is a collaborator (any role). */
    public function hasCollaborator(User $user): bool
    {
        return $this->collaborators()->where('user_id', $user->id)->exists();
    }

    /** True if the user can view this board (owner or any collaborator). */
    public function canAccess(User $user): bool
    {
        return $this->isOwner($user) || $this->hasCollaborator($user);
    }

    /** True if the user can make edits (owner or editor). */
    public function canEdit(User $user): bool
    {
        if ($this->isOwner($user)) return true;
        $collab = $this->collaborators()->where('user_id', $user->id)->first();
        return $collab && $collab->role === 'editor';
    }
}

