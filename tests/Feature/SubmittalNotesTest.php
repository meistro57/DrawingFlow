<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\SubmittalNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmittalNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_can_be_listed_for_a_submittal(): void
    {
        $author = User::factory()->create(['name' => 'Detailer One']);
        $submittal = $this->createSubmittal($author, 'NOTE-A');

        SubmittalNote::factory()->create([
            'submittal_id' => $submittal->id,
            'user_id' => $author->id,
            'message' => 'Need updated anchor bolt details.',
        ]);

        $this->actingAs($author)
            ->getJson(route('submittals.notes.index', $submittal))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Need updated anchor bolt details.')
            ->assertJsonPath('data.0.user.name', 'Detailer One');
    }

    public function test_note_can_be_created_for_submittal(): void
    {
        $author = User::factory()->create();
        $submittal = $this->createSubmittal($author, 'NOTE-B');

        $this->actingAs($author)
            ->postJson(route('submittals.notes.store', $submittal), [
                'message' => 'GC requested clouded changes on grid line C.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.message', 'GC requested clouded changes on grid line C.')
            ->assertJsonPath('data.user.id', $author->id);

        $this->assertDatabaseHas('submittal_notes', [
            'submittal_id' => $submittal->id,
            'user_id' => $author->id,
            'message' => 'GC requested clouded changes on grid line C.',
        ]);
    }

    public function test_note_message_is_required(): void
    {
        $author = User::factory()->create();
        $submittal = $this->createSubmittal($author, 'NOTE-C');

        $this->actingAs($author)
            ->postJson(route('submittals.notes.store', $submittal), [
                'message' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    private function createSubmittal(User $submittedBy, string $suffix): DrawingSubmittal
    {
        $customer = Customer::create([
            'name' => "Notes Customer {$suffix}",
            'code' => "NS-{$suffix}",
            'active' => true,
        ]);

        $project = \App\Models\Project::create([
            'project_number' => "PNS-{$suffix}",
            'name' => "Notes Project {$suffix}",
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $drawingRequest = DrawingRequest::create([
            'request_number' => "DR-{$suffix}-0001",
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $submittedBy->id,
            'assigned_to_user_id' => $submittedBy->id,
            'title' => 'Notes Drawing Request',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        return DrawingSubmittal::create([
            'submittal_number' => "SUB-{$suffix}-0001",
            'drawing_request_id' => $drawingRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $submittedBy->id,
            'purpose' => 'for_approval',
            'status' => 'draft',
        ]);
    }
}
