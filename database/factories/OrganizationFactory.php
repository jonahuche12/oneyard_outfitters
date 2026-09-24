<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'organization_code' => 'ORG-' . fake()->unique()->numerify('###') . '-' . fake()->unique()->numerify('######'),
            'name' => fake()->company(),
            'type' => fake()->randomElement([
                'school',
                'company',
                'church',
                'ngo',
                'government',
                'association',
                'other',
            ]),
            'ownership' => fake()->randomElement([
                'private',
                'public',
                'government',
                'non_profit',
                'other',
            ]),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'area' => fake()->citySuffix(),
            'lga' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'website' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}