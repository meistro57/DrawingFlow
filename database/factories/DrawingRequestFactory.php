<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrawingRequestFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        return [
            'request_number' => sprintf('DR-%s-%04d', date('Y'), $counter++),
            'project_id' => Project::factory(),
            'customer_id' => Customer::factory(),
            'requested_by_user_id' => User::factory(),
            'assigned_to_user_id' => null,
            'title' => $this->faker->words(5, true),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'drawing_type' => $this->faker->randomElement(['structural', 'misc', 'railings', 'stairs', 'other']),
            'requested_date' => now()->toDateString(),
            'required_date' => now()->addDays(14)->toDateString(),
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }

    public function readyToSubmit(): static
    {
        return $this->state(['status' => 'ready_to_submit']);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => 'urgent']);
    }
}
