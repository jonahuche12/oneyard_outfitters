<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'contact_id' => null,
            'recorded_by' => User::factory(),
            'follow_up_date' => fake()->dateTimeBetween(
                '-30 days',
                'now'
            ),
            'type' => fake()->randomElement([
                'call',
                'visit',
                'meeting',
                'whatsapp',
                'email',
                'message',
                'other',
            ]),
            'subject' => fake()->sentence(6),
            'outcome' => fake()->paragraph(),
            'next_action' => fake()->optional()->sentence(),
            'next_follow_up_date' => fake()->optional()->dateTimeBetween(
                'now',
                '+30 days'
            ),
            'status' => 'open',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forContact(Contact $contact): static
    {
        return $this->state([
            'organization_id' => $contact->organization_id,
            'contact_id' => $contact->id,
        ]);
    }
}