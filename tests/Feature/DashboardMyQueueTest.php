<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardMyQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_the_authenticated_users_queue_items(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Queue Customer',
            'code' => 'QUEUE-CUST',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'QUEUE-001',
            'name' => 'Queue Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $myRequest = DrawingRequest::create([
            'request_number' => 'DR-2026-0001',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Assigned to Me',
            'priority' => 'urgent',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'required_date' => now()->addDays(3)->toDateString(),
            'status' => 'in_progress',
        ]);

        $otherRequest = DrawingRequest::create([
            'request_number' => 'DR-2026-0002',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $otherUser->id,
            'assigned_to_user_id' => $otherUser->id,
            'title' => 'Assigned to Someone Else',
            'priority' => 'normal',
            'drawing_type' => 'misc',
            'requested_date' => now()->toDateString(),
            'required_date' => now()->addDays(10)->toDateString(),
            'status' => 'pending',
        ]);

        $mySubmittal = DrawingSubmittal::create([
            'submittal_number' => 'SUB-2026-0001',
            'drawing_request_id' => $myRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $user->id,
            'status' => 'revise_and_resubmit',
            'purpose' => 'for_approval',
        ]);

        DrawingSubmittal::create([
            'submittal_number' => 'SUB-2026-0002',
            'drawing_request_id' => $otherRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $otherUser->id,
            'status' => 'revise_and_resubmit',
            'purpose' => 'for_approval',
        ]);

        $myFabEntry = FabQueue::create([
            'submittal_id' => $mySubmittal->id,
            'project_id' => $project->id,
            'queue_number' => 'FAB-2026-0001',
            'priority' => 1,
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
            'status' => 'queued',
        ]);

        FabQueue::create([
            'submittal_id' => $mySubmittal->id,
            'project_id' => $project->id,
            'queue_number' => 'FAB-2026-0002',
            'priority' => 5,
            'assigned_to_user_id' => $otherUser->id,
            'assigned_at' => now(),
            'status' => 'queued',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('my_workload.assigned_requests', 1)
                ->where('my_workload.submittals_needing_action', 1)
                ->where('my_workload.fab_assignments', 1)
                ->where('my_workload.due_this_week', 1)
                ->where('my_queue_filter', 'all')
                ->where('my_queue_filter_counts.all', 1)
                ->where('my_queue_filter_counts.overdue', 0)
                ->where('my_queue_filter_counts.due_soon', 1)
                ->where('my_queue_filter_counts.high_priority', 1)
                ->has('my_queue', 1)
                ->where('my_queue.0.id', $myRequest->id)
                ->where('my_queue.0.title', 'Assigned to Me')
                ->has('my_submittal_work', 1)
                ->where('my_submittal_work.0.id', $mySubmittal->id)
                ->has('my_fab_assignments', 1)
                ->where('my_fab_assignments.0.id', $myFabEntry->id)
                ->missing('my_queue.1')
            );
    }

    public function test_dashboard_can_filter_assigned_queue_to_overdue_items(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Filtered Queue Customer',
            'code' => 'FILTER-CUST',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'FILTER-001',
            'name' => 'Filtered Queue Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $overdueRequest = DrawingRequest::create([
            'request_number' => 'DR-2026-0101',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Overdue Request',
            'priority' => 'high',
            'drawing_type' => 'structural',
            'requested_date' => now()->subDays(5)->toDateString(),
            'required_date' => now()->subDay()->toDateString(),
            'status' => 'pending',
        ]);

        DrawingRequest::create([
            'request_number' => 'DR-2026-0102',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Future Request',
            'priority' => 'normal',
            'drawing_type' => 'misc',
            'requested_date' => now()->toDateString(),
            'required_date' => now()->addDays(6)->toDateString(),
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['queue_filter' => 'overdue']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('my_queue_filter', 'overdue')
                ->where('my_queue_filter_counts.all', 2)
                ->where('my_queue_filter_counts.overdue', 1)
                ->where('my_queue_filter_counts.due_soon', 1)
                ->where('my_queue_filter_counts.high_priority', 1)
                ->has('my_queue', 1)
                ->where('my_queue.0.id', $overdueRequest->id)
                ->where('my_queue.0.title', 'Overdue Request')
                ->missing('my_queue.1')
            );
    }
}
