<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawingRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requires_drawing_type(): void
    {
        $user = User::factory()->create();
        [$customer, $project] = $this->createCustomerAndProject();

        $response = $this->actingAs($user)->post(route('drawing-requests.store'), [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => 'New stair package',
            'priority' => 'normal',
            'drawing_type' => '',
        ]);

        $response->assertSessionHasErrors(['drawing_type']);
        $this->assertDatabaseCount('drawing_requests', 0);
    }

    public function test_store_rejects_invalid_drawing_type(): void
    {
        $user = User::factory()->create();
        [$customer, $project] = $this->createCustomerAndProject();

        $response = $this->actingAs($user)->post(route('drawing-requests.store'), [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => 'Canopy frame request',
            'priority' => 'high',
            'drawing_type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['drawing_type']);
        $this->assertDatabaseCount('drawing_requests', 0);
    }

    public function test_store_accepts_valid_drawing_type(): void
    {
        $user = User::factory()->create();
        [$customer, $project] = $this->createCustomerAndProject();

        $response = $this->actingAs($user)->post(route('drawing-requests.store'), [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => 'Railing shop drawing request',
            'priority' => 'urgent',
            'drawing_type' => 'railings',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'title' => 'Railing shop drawing request',
            'priority' => 'urgent',
            'drawing_type' => 'railings',
            'status' => 'pending',
        ]);
    }

    /**
     * @return array{0: Customer, 1: Project}
     */
    private function createCustomerAndProject(): array
    {
        $customer = Customer::create([
            'name' => 'Validation Test Customer',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'VAL-PROJ-001',
            'name' => 'Validation Test Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        return [$customer, $project];
    }
}
