<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        return [
            'project_number' => sprintf('%s-%03d', date('Y'), $counter++),
            'name' => $this->faker->words(4, true) . ' Project',
            'customer_id' => Customer::factory(),
            'description' => $this->faker->sentence(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'start_date' => now()->subDays(30),
            'target_completion_date' => now()->addDays(90),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
