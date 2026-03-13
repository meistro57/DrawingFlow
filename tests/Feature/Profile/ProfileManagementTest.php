<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_authenticated_user_must_supply_the_current_password_to_change_password(): void
    {
        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->put(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_authenticated_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200),
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_uploading_a_new_avatar_replaces_the_previous_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($user)->put(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar-one.png', 200, 200),
        ]);

        $originalAvatarPath = $user->fresh()->avatar_path;

        $this->actingAs($user)->put(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar-two.png', 200, 200),
        ]);

        $user->refresh();

        $this->assertNotSame($originalAvatarPath, $user->avatar_path);
        Storage::disk('public')->assertMissing($originalAvatarPath);
        Storage::disk('public')->assertExists($user->avatar_path);
    }
}
