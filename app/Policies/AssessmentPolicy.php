<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assessments.view');
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->hasPermission('assessments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('assessments.create');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->hasPermission('assessments.update');
    }
}
