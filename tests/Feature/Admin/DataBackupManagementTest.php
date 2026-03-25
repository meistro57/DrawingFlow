<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\DataBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DataBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_data_backup_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Backups/Index')
                ->has('backups')
            );
    }

    public function test_non_admin_user_cannot_view_data_backup_page(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->get(route('admin.backups.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_data_backup(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.backups.store'))
            ->assertRedirect();

        $files = Storage::disk('local')->files('backups');

        $this->assertCount(1, $files);

        $payload = json_decode(Storage::disk('local')->get($files[0]), true);

        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['schema_version'] ?? null);
        $this->assertIsString($payload['signature'] ?? null);
        $this->assertArrayHasKey('tables', $payload);
        $this->assertNotEmpty($payload['tables']);
    }

    public function test_admin_can_restore_data_from_backup_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);
        $managedUser = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@drawingflow.local',
            'role' => 'detailer',
            'active' => true,
        ]);

        $service = app(DataBackupService::class);
        $backupPath = $service->createBackup();

        $managedUser->update(['name' => 'Changed Name']);

        $backupUpload = UploadedFile::fake()->createWithContent(
            'restore-backup.json',
            Storage::disk('local')->get($backupPath)
        );

        $this->actingAs($admin)
            ->post(route('admin.backups.restore'), [
                'backup_file' => $backupUpload,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_admin_restore_rejects_unsigned_backup_file(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $backupUpload = UploadedFile::fake()->createWithContent(
            'restore-backup.json',
            json_encode([
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'tables' => [],
            ])
        );

        $this->actingAs($admin)
            ->post(route('admin.backups.restore'), [
                'backup_file' => $backupUpload,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_non_admin_user_cannot_create_or_restore_backups(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->post(route('admin.backups.store'))
            ->assertForbidden();

        $backupUpload = UploadedFile::fake()->createWithContent('restore-backup.json', json_encode(['tables' => []]));

        $this->actingAs($detailer)
            ->post(route('admin.backups.restore'), [
                'backup_file' => $backupUpload,
            ])
            ->assertForbidden();
    }
}
