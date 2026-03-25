<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_boost_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.boost.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Boost/Index')
                ->has('boost.commands')
                ->has('boost.agents')
                ->has('boost.skills')
            );
    }

    public function test_admin_can_fetch_boost_mcp_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.boost.mcp-status'))
            ->assertOk()
            ->assertJsonStructure(['mcp_enabled', 'checked_at']);
    }

    public function test_admin_can_fetch_boost_mcp_stats(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        File::ensureDirectoryExists(storage_path('logs'));
        File::put(
            storage_path('logs/mcp-events.log'),
            "[2026-03-18 09:00:00] mcp.enabled status-check user=1 ip=127.0.0.1\n"
            ."[2026-03-18 09:10:00] mcp.disabled status-check user=2 ip=127.0.0.2\n"
            ."[2026-03-18 09:20:00] mcp.enabled status-check user=3 ip=127.0.0.3\n"
        );

        $this->actingAs($admin)
            ->get(route('admin.boost.mcp-stats'))
            ->assertOk()
            ->assertJsonStructure([
                'total_checks',
                'checks_last_hour',
                'checks_last_24h',
                'uptime_percent',
                'last_check_at',
                'activity_timeline',
                'recent_ips',
                'recent_users',
            ])
            ->assertJsonPath('total_checks', 3)
            ->assertJsonPath('uptime_percent', 66.7);
    }

    public function test_admin_can_fetch_boost_browser_logs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/browser.log'), "[2026-03-18 00:00:00] browser.INFO: first\n[2026-03-18 00:00:01] browser.ERROR: second\n");

        $this->actingAs($admin)
            ->get(route('admin.boost.browser-logs'))
            ->assertOk()
            ->assertJsonPath('logs.0', '[2026-03-18 00:00:00] browser.INFO: first')
            ->assertJsonPath('logs.1', '[2026-03-18 00:00:01] browser.ERROR: second');
    }

    public function test_admin_can_clear_boost_browser_logs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/browser.log'), "[2026-03-18 00:00:00] browser.INFO: first\n");

        $this->actingAs($admin)
            ->delete(route('admin.boost.browser-logs.clear'))
            ->assertOk()
            ->assertJsonPath('cleared', true);

        $this->assertSame('', File::get(storage_path('logs/browser.log')));
    }

    public function test_non_admin_user_cannot_view_boost_page(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->get(route('admin.boost.index'))
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_fetch_boost_mcp_status(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->get(route('admin.boost.mcp-status'))
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_fetch_boost_browser_logs(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->get(route('admin.boost.browser-logs'))
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_clear_boost_browser_logs(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->delete(route('admin.boost.browser-logs.clear'))
            ->assertForbidden();
    }
}
