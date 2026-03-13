<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousAvatarPath = $user->avatar_path;

        $avatarPath = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        $user->forceFill([
            'avatar_path' => $avatarPath,
        ])->save();

        if ($previousAvatarPath !== null) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        return back()->with('success', 'Avatar updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return back()->with('success', 'Password updated successfully.');
    }
}
