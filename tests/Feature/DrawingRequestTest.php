<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawingRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('drawing-requests.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_displays_drawing_requests(): void
    {
        $project = Project::factory()->create();
        DrawingRequest::factory()->count(3)->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('drawing-requests.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DrawingRequests/Index')
            ->has('requests.data', 3)
        );
    }

    public function test_index_filters_by_status(): void
    {
        $project = Project::factory()->create();
        $attrs = ['project_id' => $project->id, 'customer_id' => $project->customer_id, 'requested_by_user_id' => $this->user->id];

        DrawingRequest::factory()->pending()->create($attrs);
        DrawingRequest::factory()->inProgress()->create($attrs);

        $response = $this->actingAs($this->user)
            ->get(route('drawing-requests.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
        );
    }

    public function test_create_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('drawing-requests.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DrawingRequests/Create')
            ->has('customers')
            ->has('projects')
        );
    }

    public function test_store_creates_drawing_request(): void
    {
        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->user)->post(route('drawing-requests.store'), [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => 'Test Drawing Request',
            'description' => 'Test description',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'title' => 'Test Drawing Request',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'requested_by_user_id' => $this->user->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('drawing-requests.store'), []);

        $response->assertSessionHasErrors(['project_id', 'customer_id', 'title', 'priority']);
    }

    public function test_show_displays_drawing_request(): void
    {
        $project = Project::factory()->create();
        $request = DrawingRequest::factory()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('drawing-requests.show', $request));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('DrawingRequests/Show')
            ->has('drawingRequest')
        );
    }

    public function test_update_modifies_drawing_request(): void
    {
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put(route('drawing-requests.update', $drawingRequest), [
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'title' => 'Updated Title',
            'priority' => 'high',
            'drawing_type' => 'railings',
            'requested_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('drawing-requests.show', $drawingRequest));
        $this->assertDatabaseHas('drawing_requests', [
            'id' => $drawingRequest->id,
            'title' => 'Updated Title',
            'priority' => 'high',
        ]);
    }

    public function test_destroy_soft_deletes_drawing_request(): void
    {
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('drawing-requests.destroy', $drawingRequest));

        $response->assertRedirect(route('drawing-requests.index'));
        $this->assertSoftDeleted('drawing_requests', ['id' => $drawingRequest->id]);
    }

    public function test_assign_sets_assigned_user(): void
    {
        $detailer = User::factory()->create(['role' => 'detailer', 'active' => true]);
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->pending()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('drawing-requests.assign', $drawingRequest),
            ['user_id' => $detailer->id]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'id' => $drawingRequest->id,
            'assigned_to_user_id' => $detailer->id,
        ]);
    }

    public function test_mark_ready_transitions_status(): void
    {
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->inProgress()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('drawing-requests.mark-ready', $drawingRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'id' => $drawingRequest->id,
            'status' => 'ready_to_submit',
        ]);
    }

    public function test_cancel_transitions_status(): void
    {
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->pending()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('drawing-requests.cancel', $drawingRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'id' => $drawingRequest->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_hold_transitions_status(): void
    {
        $project = Project::factory()->create();
        $drawingRequest = DrawingRequest::factory()->inProgress()->create([
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'requested_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('drawing-requests.hold', $drawingRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_requests', [
            'id' => $drawingRequest->id,
            'status' => 'on_hold',
        ]);
    }
}
