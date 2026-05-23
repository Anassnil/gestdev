<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AIModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class AIModelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return \Gate::allows('manage-ai');
    }

    public function view(User $user, AIModel $model)
    {
        return \Gate::allows('manage-ai');
    }

    public function create(User $user)
    {
        return \Gate::allows('manage-ai');
    }

    public function update(User $user, AIModel $model)
    {
        return \Gate::allows('manage-ai');
    }

    public function delete(User $user, AIModel $model)
    {
        return \Gate::allows('manage-ai');
    }
}
