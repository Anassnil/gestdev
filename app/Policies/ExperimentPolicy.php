<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Experiment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExperimentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return \Gate::allows('manage-ai');
    }

    public function view(User $user, Experiment $experiment)
    {
        return \Gate::allows('manage-ai');
    }

    public function create(User $user)
    {
        return \Gate::allows('manage-ai');
    }

    public function promote(User $user, Experiment $experiment)
    {
        return \Gate::allows('manage-ai');
    }
}
