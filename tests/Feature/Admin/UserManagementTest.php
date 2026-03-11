<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_non_admin_user_cannot_view_user_management_page(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($detailer)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_a_users_role_and_active_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'role' => 'manager',
            'active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'manager',
            'active' => 0,
        ]);
    }

    public function test_admin_can_create_a_new_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Manager',
            'email' => 'new.manager@drawingflow.local',
            'role' => 'manager',
            'title' => 'Production Manager',
            'active' => true,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'New Manager',
            'email' => 'new.manager@drawingflow.local',
            'role' => 'manager',
            'title' => 'Production Manager',
            'active' => 1,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'role' => 'admin',
            'active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'active' => 1,
        ]);
    }

    public function test_non_admin_user_cannot_create_users(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($detailer)->post(route('admin.users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@drawingflow.local',
            'role' => 'viewer',
            'active' => true,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@drawingflow.local',
        ]);
    }
}
