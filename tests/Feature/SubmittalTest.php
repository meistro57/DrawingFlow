<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmittalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Customer $customer;
    private DrawingRequest $drawingRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->customer = Customer::factory()->create();
        $this->project = Project::factory()->create(['customer_id' => $this->customer->id]);
        $this->drawingRequest = DrawingRequest::factory()->readyToSubmit()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'requested_by_user_id' => $this->user->id,
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('submittals.index'))->assertRedirect(route('login'));
    }

    public function test_index_displays_submittals(): void
    {
        DrawingSubmittal::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('submittals.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Submittals/Index')
            ->has('submittals.data', 2)
        );
    }

    public function test_index_filters_by_status(): void
    {
        DrawingSubmittal::factory()->draft()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);
        DrawingSubmittal::factory()->submitted()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('submittals.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('submittals.data', 1));
    }

    public function test_create_from_request_creates_submittal(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('submittals.create-from-request', $this->drawingRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_submittals', [
            'drawing_request_id' => $this->drawingRequest->id,
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'revision' => 'A',
            'status' => 'draft',
        ]);
    }

    public function test_create_from_request_updates_drawing_request_status(): void
    {
        $this->actingAs($this->user)
            ->post(route('submittals.create-from-request', $this->drawingRequest));

        $this->assertDatabaseHas('drawing_requests', [
            'id' => $this->drawingRequest->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_show_displays_submittal(): void
    {
        $submittal = DrawingSubmittal::factory()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('submittals.show', $submittal));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Submittals/Show')
            ->has('submittal')
        );
    }

    public function test_submit_transitions_status_to_submitted(): void
    {
        $submittal = DrawingSubmittal::factory()->draft()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('submittals.submit', $submittal));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_submittals', [
            'id' => $submittal->id,
            'status' => 'submitted',
        ]);
        $this->assertNotNull($submittal->fresh()->submitted_at);
    }

    public function test_process_approval_creates_approval_record(): void
    {
        $submittal = DrawingSubmittal::factory()->submitted()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('submittals.process-approval', $submittal),
            [
                'approval_type' => 'approved',
                'reviewer_name' => 'John Smith',
                'reviewer_title' => 'Architect',
                'reviewer_company' => 'Smith & Partners',
                'approval_notes' => 'Looks good',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('submittal_approvals', [
            'submittal_id' => $submittal->id,
            'approval_type' => 'approved',
            'reviewer_name' => 'John Smith',
        ]);
        $this->assertDatabaseHas('drawing_submittals', [
            'id' => $submittal->id,
            'status' => 'approved',
        ]);
    }

    public function test_approved_submittal_creates_fab_queue_entry(): void
    {
        $submittal = DrawingSubmittal::factory()->submitted()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $this->actingAs($this->user)->post(
            route('submittals.process-approval', $submittal),
            ['approval_type' => 'approved']
        );

        $this->assertDatabaseHas('fab_queue', [
            'submittal_id' => $submittal->id,
            'project_id' => $this->project->id,
            'status' => 'queued',
        ]);
    }

    public function test_create_revision_increments_revision_letter(): void
    {
        $submittal = DrawingSubmittal::factory()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
            'revision' => 'A',
            'status' => 'revise_and_resubmit',
        ]);

        $response = $this->actingAs($this->user)->post(route('submittals.create-revision', $submittal));

        $response->assertRedirect();
        $this->assertDatabaseHas('drawing_submittals', [
            'drawing_request_id' => $this->drawingRequest->id,
            'revision' => 'B',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('drawing_submittals', [
            'id' => $submittal->id,
            'status' => 'superseded',
        ]);
    }

    public function test_process_approval_validates_required_fields(): void
    {
        $submittal = DrawingSubmittal::factory()->submitted()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('submittals.process-approval', $submittal),
            []
        );

        $response->assertSessionHasErrors(['approval_type']);
    }

    public function test_destroy_soft_deletes_submittal(): void
    {
        $submittal = DrawingSubmittal::factory()->draft()->create([
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'submitted_by_user_id' => $this->user->id,
            'drawing_request_id' => $this->drawingRequest->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('submittals.destroy', $submittal));

        $response->assertRedirect(route('submittals.index'));
        $this->assertSoftDeleted('drawing_submittals', ['id' => $submittal->id]);
    }
}
