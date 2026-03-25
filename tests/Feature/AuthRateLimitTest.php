<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create([
            'email' => 'limit@example.com',
            'password' => Hash::make('correct-password'),
            'active' => true,
        ]);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login'), [
                'email' => 'limit@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), [
            'email' => 'limit@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many login attempts.',
            session('errors')->first('email')
        );
    }

    public function test_registration_is_rate_limited_after_repeated_attempts(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('register'), [
                'name' => '',
                'email' => '',
                'password' => '',
                'password_confirmation' => '',
            ])->assertSessionHasErrors(['name', 'email', 'password']);
        }

        $this->post(route('register'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors([
            'email' => 'Too many registration attempts. Please try again in 60 seconds.',
        ]);
    }
}
