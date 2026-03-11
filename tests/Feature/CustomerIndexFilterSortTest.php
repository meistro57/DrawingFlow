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
        Customer::create(['name' => 'Alpha Steel', 'code' => 'ALPHA', 'active' => true]);
        Customer::create(['name' => 'Bravo Fabrication', 'code' => 'BRAVO', 'active' => true]);

        $response = $this->actingAs($user)->get(route('customers.index', [
            'search' => 'Alpha',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'Alpha')
            ->has('customers.data', 1)
            ->where('customers.data.0.code', 'ALPHA'));
    }

    public function test_customer_index_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        Customer::create(['name' => 'Active Co', 'code' => 'ACTIVE', 'active' => true]);
        Customer::create(['name' => 'Inactive Co', 'code' => 'INACTIVE', 'active' => false]);

        $response = $this->actingAs($user)->get(route('customers.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'inactive')
            ->has('customers.data', 1)
            ->where('customers.data.0.code', 'INACTIVE'));
    }

    public function test_customer_index_can_sort_by_name_ascending(): void
    {
        $user = User::factory()->create();
        Customer::create(['name' => 'Zulu Metals', 'code' => 'ZULU', 'active' => true]);
        Customer::create(['name' => 'Alpha Metals', 'code' => 'ALPHA', 'active' => true]);

        $response = $this->actingAs($user)->get(route('customers.index', [
            'sort' => 'name_asc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name_asc')
            ->where('customers.data.0.code', 'ALPHA')
            ->where('customers.data.1.code', 'ZULU'));
    }
}
