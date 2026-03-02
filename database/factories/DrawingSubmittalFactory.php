<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrawingSubmittalFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;

        return [
            'submittal_number' => sprintf('SUB-%s-%04d', date('Y'), $counter++),
            'drawing_request_id' => DrawingRequest::factory(),
            'project_id' => Project::factory(),
            'customer_id' => Customer::factory(),
            'revision' => 'A',
            'submitted_by_user_id' => User::factory(),
            'submitted_at' => null,
            'drawing_discipline' => null,
            'purpose' => 'for_approval',
            'status' => 'draft',
            'notes' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => 'approved',
            'submitted_at' => now()->subDays(7),
            'approval_received_at' => now(),
            'approval_type' => 'approved',
        ]);
    }
}
