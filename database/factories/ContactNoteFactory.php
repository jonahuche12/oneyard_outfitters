<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactNote>
 */
class ContactNoteFactory extends Factory
{
    protected $model = ContactNote::class;

    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'recorded_by' => User::factory(),
            'note' => fake()->paragraph(),
        ];
    }
}
