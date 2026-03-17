<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerIndexFilterSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_index_can_filter_by_search_term(): void
    {
        $user = User::factory()->create();
        Customer::create(['name' => 'Alpha Steel', 'active' => true]);
        Customer::create(['name' => 'Bravo Fabrication', 'active' => true]);
        $response = $this->actingAs($user)->get(route('customers.index', [
            'search' => 'Alpha',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Alpha')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Alpha Steel'));
    }

    public function test_customer_index_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        Customer::create(['name' => 'Active Co', 'active' => true]);
        Customer::create(['name' => 'Inactive Co', 'active' => false]);
        $response = $this->actingAs($user)->get(route('customers.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'inactive')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Inactive Co'));
    }

    public function test_customer_index_can_sort_by_name_ascending(): void
    {
        $user = User::factory()->create();
        Customer::create(['name' => 'Zulu Metals', 'active' => true]);
        Customer::create(['name' => 'Alpha Metals', 'active' => true]);
        $response = $this->actingAs($user)->get(route('customers.index', [
            'sort' => 'name_asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name_asc')
            ->where('customers.data.0.name', 'Alpha Metals')
            ->where('customers.data.1.name', 'Zulu Metals'));
    }
}
