<?php

namespace App\Policies;

use App\Models\ProductSpecification;
use App\Models\User;

class ProductSpecificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('specifications.view');
    }

    public function view(User $user, ProductSpecification $productSpecification): bool
    {
        return $user->hasPermission('specifications.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('specifications.create');
    }

    public function update(User $user, ProductSpecification $productSpecification): bool
    {
        return $user->hasPermission('specifications.update');
    }
}
