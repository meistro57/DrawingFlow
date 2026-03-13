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
            'name' => 'Updated Detailer',
            'email' => 'updated.detailer@drawingflow.local',
            'role' => 'manager',
            'title' => 'Senior Detailer',
            'active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Detailer',
            'email' => 'updated.detailer@drawingflow.local',
            'role' => 'manager',
            'title' => 'Senior Detailer',
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
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'title' => $admin->title,
            'active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'active' => 1,
        ]);
    }

    public function test_admin_can_update_user_password(): void
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
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'title' => $user->title,
            'active' => true,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertTrue(password_verify('Password123!', $user->fresh()->password));
    }

    public function test_admin_can_delete_a_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
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

    public function test_non_admin_user_cannot_delete_users(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($detailer)->delete(route('admin.users.destroy', $user));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }
}
