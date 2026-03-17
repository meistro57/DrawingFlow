<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Mark',
            'email' => 'mark@drawingflow.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'title' => 'Owner / Fabricator',
            'active' => true,
        ]);

        // Create a detailer user
        User::create([
            'name' => 'Demo Detailer',
            'email' => 'detailer@drawingflow.local',
            'password' => Hash::make('password'),
            'role' => 'detailer',
            'title' => 'Steel Detailer',
            'active' => true,
        ]);

        // Create sample customers
        $customer1 = Customer::create([
            'name' => 'ABC General Contractors',
            'email' => 'submittals@abcgc.example.com',
            'phone' => '(555) 123-4567',
            'city' => 'Houston',
            'state' => 'TX',
            'country' => 'USA',
            'active' => true,
        ]);

        $customer2 = Customer::create([
            'name' => 'Smith Engineering',
            'email' => 'drawings@smitheng.example.com',
            'phone' => '(555) 987-6543',
            'city' => 'Dallas',
            'state' => 'TX',
            'country' => 'USA',
            'active' => true,
        ]);

        // Create sample projects
        Project::create([
            'project_number' => '2026-001',
            'name' => 'Downtown Office Tower - Steel Package',
            'customer_id' => $customer1->id,
            'description' => 'Structural steel for 12-story office building',
            'city' => 'Houston',
            'state' => 'TX',
            'start_date' => '2026-01-15',
            'target_completion_date' => '2026-06-30',
            'status' => 'active',
        ]);

        Project::create([
            'project_number' => '2026-002',
            'name' => 'Warehouse Mezzanine Expansion',
            'customer_id' => $customer2->id,
            'description' => 'Mezzanine addition with stairs and railings',
            'city' => 'Dallas',
            'state' => 'TX',
            'start_date' => '2026-02-01',
            'target_completion_date' => '2026-04-15',
            'status' => 'active',
        ]);

        // Create customer workflows
        $customer1->workflow()->create([
            'requires_architect_approval' => true,
            'requires_engineer_approval' => true,
            'requires_gc_approval' => true,
            'preferred_submittal_method' => 'email',
            'submittal_email' => 'submittals@abcgc.example.com',
            'approval_sla_days' => 14,
        ]);

        $customer2->workflow()->create([
            'requires_architect_approval' => false,
            'requires_engineer_approval' => true,
            'requires_gc_approval' => true,
            'preferred_submittal_method' => 'email',
            'submittal_email' => 'drawings@smitheng.example.com',
            'approval_sla_days' => 10,
        ]);
    }
}
