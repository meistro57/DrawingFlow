<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\User;
use App\Notifications\SubmittalApprovalRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_index_returns_unread_count_and_items(): void
    {
        $actor = User::factory()->create(['name' => 'Approval Recorder']);
        $recipient = User::factory()->create();
        $submittal = $this->createSubmittal($recipient, $recipient, 'NOTI-A');

        $recipient->notify(new SubmittalApprovalRecorded($submittal, 'approved', $actor->name));

        $this->actingAs($recipient)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.submittal_number', $submittal->submittal_number);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $actor = User::factory()->create(['name' => 'Approval Recorder']);
        $recipient = User::factory()->create();
        $submittal = $this->createSubmittal($recipient, $recipient, 'NOTI-B');

        $recipient->notify(new SubmittalApprovalRecorded($submittal, 'approved_as_noted', $actor->name));
        $notification = $recipient->notifications()->first();

        $this->actingAs($recipient)
            ->postJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($recipient->fresh()->notifications()->first()->read_at);
    }

    public function test_process_approval_creates_notifications_for_submitted_by_and_assigned_user(): void
    {
        $submittedBy = User::factory()->create();
        $assignedTo = User::factory()->create();
        $approver = User::factory()->create(['name' => 'Reviewer Name']);

        $submittal = $this->createSubmittal($submittedBy, $assignedTo, 'NOTI-C');

        $this->actingAs($approver)
            ->post(route('submittals.process-approval', $submittal), [
                'approval_type' => 'approved',
                'reviewer_name' => 'GC Reviewer',
                'reviewer_title' => 'Project Manager',
                'reviewer_company' => 'ACME GC',
                'reviewer_email' => 'pm@acme.test',
                'approval_notes' => 'Approved for fabrication.',
                'conditions' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertSame(1, $submittedBy->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $assignedTo->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $approver->fresh()->unreadNotifications()->count());
    }

    public function test_mark_all_notifications_as_read_clears_unread_count(): void
    {
        $actor = User::factory()->create(['name' => 'Approval Recorder']);
        $recipient = User::factory()->create();
        $submittalA = $this->createSubmittal($recipient, $recipient, 'NOTI-D1');
        $submittalB = $this->createSubmittal($recipient, $recipient, 'NOTI-D2');

        $recipient->notify(new SubmittalApprovalRecorded($submittalA, 'approved', $actor->name));
        $recipient->notify(new SubmittalApprovalRecorded($submittalB, 'revise_and_resubmit', $actor->name));

        $this->actingAs($recipient)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $recipient->fresh()->unreadNotifications()->count());
    }

    private function createSubmittal(User $submittedBy, User $assignedTo, string $suffix): DrawingSubmittal
    {
        $customer = Customer::create([
            'name' => "Notification Customer {$suffix}",
            'code' => "NC-{$suffix}",
            'active' => true,
        ]);

        $project = \App\Models\Project::create([
            'project_number' => "NP-{$suffix}",
            'name' => "Notification Project {$suffix}",
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $drawingRequest = DrawingRequest::create([
            'request_number' => "DR-{$suffix}-0001",
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $submittedBy->id,
            'assigned_to_user_id' => $assignedTo->id,
            'title' => 'Notification Request',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'status' => 'submitted',
        ]);

        return DrawingSubmittal::create([
            'submittal_number' => "SUB-{$suffix}-0001",
            'drawing_request_id' => $drawingRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $submittedBy->id,
            'purpose' => 'for_approval',
            'status' => 'submitted',
        ]);
    }
}
