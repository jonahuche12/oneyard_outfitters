<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),

            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional()->firstName(),

            'position' => fake()->randomElement([
                'Proprietor',
                'Principal',
                'Administrator',
                'Procurement Officer',
                'Bursar',
                'Manager',
                'Director',
                'Coordinator',
            ]),

            'phone' => fake()->phoneNumber(),
            'alternate_phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'whatsapp' => fake()->optional()->phoneNumber(),

            'is_primary' => false,
            'is_active' => true,

            'notes' => fake()->optional()->sentence(),
        ];
    }
}