<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\Project;
use App\Models\SubmittalFile;
use App\Models\SubmittalNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'mark@drawingflow.local')->first()
            ?? User::query()->first();

        $detailer = User::query()->where('email', 'detailer@drawingflow.local')->first()
            ?? User::query()->where('role', 'detailer')->first()
            ?? $admin;

        if (! $admin || ! $detailer) {
            return;
        }

        $customers = Customer::query()->where('active', true)->orderBy('id')->limit(3)->get();

        foreach ($customers as $index => $customer) {
            $projectNumber = sprintf('DEMO-%03d', $index + 1);

            $project = Project::query()->firstOrCreate(
                ['project_number' => $projectNumber],
                [
                    'name' => $customer->name.' Fabrication Package',
                    'customer_id' => $customer->id,
                    'description' => 'Demo project seeded for realistic workflow testing.',
                    'city' => $customer->city ?: 'Houston',
                    'state' => $customer->state ?: 'TX',
                    'status' => 'active',
                    'start_date' => now()->subDays(14 + ($index * 7))->toDateString(),
                    'target_completion_date' => now()->addDays(45 + ($index * 10))->toDateString(),
                    'model_link' => 'https://models.example.com/projects/'.strtolower(str_replace(' ', '-', $projectNumber)),
                    'notes' => 'Seeded by DemoWorkflowSeeder for QA and demos.',
                ]
            );

            $requestNumber = sprintf('DR-%s-%04d', now()->format('Y'), $index + 900);

            $drawingRequest = DrawingRequest::query()->firstOrCreate(
                ['request_number' => $requestNumber],
                [
                    'project_id' => $project->id,
                    'customer_id' => $customer->id,
                    'requested_by_user_id' => $admin->id,
                    'assigned_to_user_id' => $detailer->id,
                    'title' => 'Shop Drawings - '.$project->project_number,
                    'description' => 'Prepare shop drawings and connection details for phase '.($index + 1).'.',
                    'priority' => $index === 0 ? 'high' : 'normal',
                    'drawing_type' => 'structural',
                    'job_number' => 'JOB-'.$project->project_number,
                    'required_date' => now()->addDays(10 + ($index * 3))->toDateString(),
                    'requested_date' => now()->subDays(2 + $index)->toDateString(),
                    'status' => $index === 0 ? 'in_progress' : 'ready_to_submit',
                    'notes' => 'Seeded drawing request for realistic dashboard and queue data.',
                    'import_source' => 'demo-seed',
                ]
            );

            $submittalNumber = sprintf('SUB-%s-%04d', now()->format('Y'), $index + 500);

            $submittal = DrawingSubmittal::query()->firstOrCreate(
                ['submittal_number' => $submittalNumber],
                [
                    'drawing_request_id' => $drawingRequest->id,
                    'project_id' => $project->id,
                    'customer_id' => $customer->id,
                    'revision' => 'A',
                    'submitted_by_user_id' => $detailer->id,
                    'submitted_at' => now()->subDays($index + 1),
                    'drawing_discipline' => 'commercial_structural',
                    'purpose' => $index === 0 ? 'for_fab' : 'for_approval',
                    'status' => $index === 0 ? 'approved_as_noted' : 'submitted',
                    'sent_to_customer' => true,
                    'model_link' => $project->model_link,
                    'notes' => 'Seeded submittal for end-to-end demo workflow.',
                    'internal_notes' => 'Check embed plates and anchor bolt dimensions.',
                    'import_source' => 'demo-seed',
                ]
            );

            SubmittalFile::query()->firstOrCreate(
                [
                    'submittal_id' => $submittal->id,
                    'original_filename' => $project->project_number.'-shop-drawings.pdf',
                ],
                [
                    'file_type' => 'drawing',
                    'filename' => strtolower($project->project_number).'-shop-drawings.pdf',
                    'file_path' => 'seeded/'.$project->project_number.'/shop-drawings.pdf',
                    'file_size' => 512000,
                    'mime_type' => 'application/pdf',
                    'version' => 1,
                    'is_current' => true,
                    'uploaded_by_user_id' => $detailer->id,
                    'uploaded_at' => now()->subDays($index + 1),
                    'notes' => 'Seeded demo PDF metadata entry.',
                ]
            );

            SubmittalNote::query()->firstOrCreate(
                [
                    'submittal_id' => $submittal->id,
                    'user_id' => $admin->id,
                    'message' => 'Seeded note: verify dimensions against latest field measurements.',
                ]
            );

            FabQueue::query()->firstOrCreate(
                ['queue_number' => sprintf('FAB-%s-%04d', now()->format('Y'), $index + 700)],
                [
                    'submittal_id' => $submittal->id,
                    'project_id' => $project->id,
                    'priority' => $index + 1,
                    'material_requirements' => 'A36 plate, HSS members, and anchor hardware.',
                    'cnc_files_attached' => $index !== 2,
                    'assigned_to_user_id' => $detailer->id,
                    'assigned_at' => now()->subDays($index + 1),
                    'status' => $index === 2 ? 'queued' : 'in_progress',
                    'started_at' => now()->subDays($index + 1),
                    'notes' => 'Seeded fab queue entry for schedule visualization.',
                    'shop_notes' => 'Coordinate weld symbols with latest approval set.',
                    'date_released' => now()->subDays($index + 1)->toDateString(),
                    'attachments_count' => 1,
                    'model_link' => $project->model_link,
                    'import_source' => 'demo-seed',
                ]
            );
        }
    }
}
