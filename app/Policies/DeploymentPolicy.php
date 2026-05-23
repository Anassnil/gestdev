<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Deployment;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeploymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return \Gate::allows('manage-ai');
    }

    public function view(User $user, Deployment $deployment)
    {
        return \Gate::allows('manage-ai');
    }

    public function deploy(User $user)
    {
        return \Gate::allows('manage-ai');
    }
}
