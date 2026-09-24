<?php

namespace App\Actions\ProductSpecifications;

use App\Models\ProductSpecification;
use App\Models\User;

class CreateProductSpecification
{
    public function execute(
        User $user,
        array $attributes
    ): ProductSpecification {
        $attributes['created_by'] = $user->id;

        return ProductSpecification::create($attributes);
    }
}