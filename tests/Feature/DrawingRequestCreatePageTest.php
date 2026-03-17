<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DrawingRequestCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_includes_customer_and_project_data_for_autofill(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Acme Steel',
            'address' => '100 Main St',
            'city' => 'Houston',
            'state' => 'TX',
            'zip' => '77001',
            'country' => 'USA',
            'active' => true,
        ]);

        Project::create([
            'project_number' => 'JOB-100',
            'name' => 'Office Tower',
            'customer_id' => $customer->id,
            'address' => '200 Jobsite Ave',
            'city' => 'Dallas',
            'state' => 'TX',
            'zip' => '75201',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('drawing-requests.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DrawingRequests/Create')
            ->where('customers.0.name', 'Acme Steel')
            ->where('customers.0.address', '100 Main St')
            ->where('projects.0.project_number', 'JOB-100')
            ->where('projects.0.address', '200 Jobsite Ave')
            ->where('projects.0.customer.name', 'Acme Steel'));
    }
}
