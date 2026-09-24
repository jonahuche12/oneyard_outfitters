<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'assessed_by' => User::factory(),
            'assessment_date' => fake()->date(),
            'status' => 'draft',
            'needs' => fake()->paragraph(),
            'current_supplier' => fake()->optional()->company(),
            'procurement_process' => fake()->optional()->paragraph(),
            'estimated_budget' => fake()->optional()->randomFloat(2, 100000, 50000000),
            'estimated_demand' => fake()->optional()->sentence(),
            'buying_timeline' => fake()->optional()->randomElement([
                'immediate',
                'within 30 days',
                'within 3 months',
                'next quarter',
                'annual procurement',
            ]),
            'decision_maker' => fake()->optional()->name(),
            'observations' => fake()->optional()->paragraph(),
            'opportunities' => fake()->optional()->paragraph(),
            'risks' => fake()->optional()->paragraph(),
            'recommendation' => fake()->optional()->paragraph(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}