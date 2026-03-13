<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Http\Requests\Admin\UpdateUserManagementRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function store(StoreManagedUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return back()->with('success', 'User created successfully.');
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'role', 'title', 'active', 'created_at'])
                ->orderBy('name')
                ->paginate(25),
            'roleOptions' => ['admin', 'manager', 'detailer', 'viewer'],
        ]);
    }

    public function update(UpdateUserManagementRequest $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user) && $request->boolean('active') === false) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $validated = $request->validated();

        if (! $request->filled('password')) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', "Updated {$user->name} successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
