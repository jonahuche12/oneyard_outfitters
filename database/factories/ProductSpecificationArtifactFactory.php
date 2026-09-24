<?php

namespace Database\Factories;

use App\Models\ProductSpecification;
use App\Models\ProductSpecificationArtifact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSpecificationArtifactFactory extends Factory
{
    protected $model = ProductSpecificationArtifact::class;

    public function definition(): array
    {
        return [
            'product_specification_id' => ProductSpecification::factory(),

            'artifact_type' => fake()->randomElement([
                'design',
                'material',
                'logo',
                'branding',
                'sample',
                'size_chart',
                'measurement',
                'reference',
                'other',
            ]),

            'title' => fake()->sentence(4),

            'description' => fake()->optional()->paragraph(),

            'file_path' => 'product-specifications/'
                . fake()->uuid()
                . '/reference.pdf',

            'original_filename' => fake()->randomElement([
                'design-reference.jpg',
                'material-reference.jpg',
                'official-logo.png',
                'size-chart.pdf',
                'approved-sample.jpg',
            ]),

            'mime_type' => fake()->randomElement([
                'image/jpeg',
                'image/png',
                'application/pdf',
            ]),

            'file_size' => fake()->numberBetween(
                10_000,
                5_000_000
            ),

            'uploaded_by' => User::factory(),

            'is_current' => true,
        ];
    }
}