<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $throttleKey = sprintf('register:%s', (string) $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'detailer',
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
