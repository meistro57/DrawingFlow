<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectModelLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_store_accepts_model_link(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Model Link Customer',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'project_number' => 'MODEL-001',
            'name' => 'Model Link Project',
            'customer_id' => $customer->id,
            'status' => 'active',
            'model_link' => 'https://models.example.com/view/abc123',
        ]);

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'project_number' => 'MODEL-001',
            'model_link' => 'https://models.example.com/view/abc123',
        ]);
    }

    public function test_project_store_rejects_invalid_model_link(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Validation Customer',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'project_number' => 'MODEL-002',
                'name' => 'Invalid URL Project',
                'customer_id' => $customer->id,
                'status' => 'active',
                'model_link' => 'not-a-url',
            ])
            ->assertSessionHasErrors(['model_link']);
    }

    public function test_project_show_includes_model_link_in_inertia_payload(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Payload Customer',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'MODEL-003',
            'name' => 'Payload Project',
            'customer_id' => $customer->id,
            'status' => 'active',
            'model_link' => 'https://models.example.com/view/payload',
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Show')
                ->where('project.model_link', 'https://models.example.com/view/payload')
            );
    }
}
