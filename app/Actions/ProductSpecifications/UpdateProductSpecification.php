<?php

namespace App\Actions\ProductSpecifications;

use App\Models\ProductSpecification;

class UpdateProductSpecification
{
    public function execute(
        ProductSpecification $productSpecification,
        array $attributes
    ): ProductSpecification {
        unset($attributes['created_by']);

        $productSpecification->update($attributes);

        return $productSpecification->refresh();
    }
}