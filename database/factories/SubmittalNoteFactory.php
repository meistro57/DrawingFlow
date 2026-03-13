<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubmittalNote>
 */
class SubmittalNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submittal_id' => 1,
            'user_id' => User::factory(),
            'message' => $this->faker->sentence(12),
        ];
    }
}
