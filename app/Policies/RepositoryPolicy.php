<?php

namespace App\Policies;

use App\Models\Repository;
use App\Models\User;

class RepositoryPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user;
    }

    public function view(User $user, Repository $repository): bool
    {
        if ((int) $repository->owner_id === (int) $user->id) {
            return true;
        }

        if ($repository->visibility === 'public') {
            return true;
        }

        return $repository->collaborators()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return (bool) $user;
    }

    public function update(User $user, Repository $repository): bool
    {
        if ((int) $repository->owner_id === (int) $user->id) {
            return true;
        }

        return $repository->collaborators()
            ->where('user_id', $user->id)
            ->whereIn('role', ['admin', 'maintainer'])
            ->exists();
    }

    public function delete(User $user, Repository $repository): bool
    {
        return (int) $repository->owner_id === (int) $user->id;
    }
}
