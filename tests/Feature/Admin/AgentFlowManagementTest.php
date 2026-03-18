<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgentFlowManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_agent_flow_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.agent-flow.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AgentFlow/Index')
                ->has('availableNodeTypes')
                ->has('initialNodes')
                ->has('initialEdges')
            );
    }

    public function test_non_admin_user_cannot_view_agent_flow_page(): void
    {
        $detailer = User::factory()->create([
            'role' => 'detailer',
            'active' => true,
        ]);

        $this->actingAs($detailer)
            ->get(route('admin.agent-flow.index'))
            ->assertForbidden();
    }
}
