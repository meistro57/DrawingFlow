<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_project_request_and_submittal_matches(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Search Customer',
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => 'FIND-100',
            'name' => 'Find Me Project',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $request = DrawingRequest::create([
            'request_number' => 'DR-2026-7777',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Find Request',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $submittal = DrawingSubmittal::create([
            'submittal_number' => 'SUB-2026-7777',
            'drawing_request_id' => $request->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $user->id,
            'purpose' => 'for_approval',
            'drawing_discipline' => 'commercial_structural',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['q' => '7777']));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                ['type', 'title', 'subtitle', 'url'],
            ],
        ]);

        $payload = $response->json('data');

        $this->assertTrue(collect($payload)->contains(fn ($row) => ($row['url'] ?? '') === route('drawing-requests.show', $request)));
        $this->assertTrue(collect($payload)->contains(fn ($row) => ($row['url'] ?? '') === route('submittals.show', $submittal)));
    }

    public function test_global_search_returns_empty_for_short_query(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('search.global', ['q' => ' ']));

        $response->assertOk();
        $response->assertExactJson([
            'data' => [],
        ]);
    }
}
