<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateAbbreviationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_store_rejects_non_abbreviated_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'name' => 'State Validation Customer',
                'state' => 'Texas',
                'active' => true,
            ])
            ->assertSessionHasErrors(['state']);
    }

    public function test_project_store_rejects_non_abbreviated_state(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Project State Customer',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'project_number' => 'STATE-001',
                'name' => 'State Validation Project',
                'customer_id' => $customer->id,
                'status' => 'active',
                'state' => 'Illinois',
            ])
            ->assertSessionHasErrors(['state']);
    }

    public function test_project_update_accepts_abbreviated_state(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Abbreviation Customer',
            'active' => true,
        ]);
        $project = Project::create([
            'project_number' => 'STATE-002',
            'name' => 'Abbreviation Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put(route('projects.update', $project), [
                'project_number' => 'STATE-002',
                'name' => 'Abbreviation Project',
                'customer_id' => $customer->id,
                'status' => 'active',
                'state' => 'TX',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'state' => 'TX',
        ]);
    }
}
