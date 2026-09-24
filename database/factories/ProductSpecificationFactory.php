<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSpecificationFactory extends Factory
{
    protected $model = ProductSpecification::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),

            'contact_id' => null,

            'created_by' => User::factory(),

            'specification_date' => fake()->date(),

            'item_name' => fake()->randomElement([
                'White School Shirt',
                'Green School Trousers',
                'School Shorts',
                'School Pinafore',
                'Sportswear',
                'School Bag',
                'Branded T-Shirt',
            ]),

            'product_type' => fake()->randomElement([
                'uniform',
                'sportswear',
                'bag',
                'shoe',
                'branded_item',
                'school_supply',
                'other',
            ]),

            'description' => fake()->paragraph(),

            'unit' => fake()->randomElement([
                'piece',
                'pair',
                'set',
                'pack',
            ]),

            'unit_price' => fake()->randomFloat(
                2,
                1000,
                100000
            ),

            'price_updated_at' => fake()->optional()->date(),

            'material' => fake()->optional()->randomElement([
                'White Cotton',
                'Green Spider',
                'Polyester',
                'Cotton Blend',
                'Approved organization fabric',
            ]),

            'material_details' => fake()->optional()->paragraph(),

            'design_details' => fake()->optional()->paragraph(),

            'size_details' => fake()->optional()->sentence(),

            'branding_details' => fake()->optional()->sentence(),

            'quality_requirements' => fake()->optional()->paragraph(),

            'special_instructions' => fake()->optional()->paragraph(),

            'status' => 'draft',

            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => 'confirmed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
        ]);
    }

    public function forContact(Contact $contact): static
    {
        return $this->state([
            'organization_id' => $contact->organization_id,
            'contact_id' => $contact->id,
        ]);
    }
}