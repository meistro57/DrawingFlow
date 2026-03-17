<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SplFileObject;

class ImportLegacyCsvData extends Command
{
    protected $signature = 'data:import-legacy-csv';

    protected $description = 'Import legacy drawing workflow CSV files into the database';

    private string $runToken;

    private int $requestSequence = 1;

    private int $submittalSequence = 1;

    private int $fabSequence = 1;

    public function handle(): int
    {
        $this->runToken = now()->format('ymdHis');

        $shopRequests = $this->readCsv(base_path('Shop Drawing Request.csv'));
        $submittalRows = $this->readCsv(base_path('Drawing Submittal Log.csv'));
        $fabRows = $this->readCsv(base_path('Fabrication Drawing Log.csv'));

        DB::transaction(function () use ($shopRequests, $submittalRows, $fabRows): void {
            FabQueue::query()->where('import_source', 'legacy_fabrication_log')->delete();
            DrawingSubmittal::query()->where('import_source', 'legacy_submittal_log')->delete();
            DrawingRequest::query()->where('import_source', 'legacy_shop_request')->delete();

            $this->importShopRequests($shopRequests);
            $this->importSubmittals($submittalRows);
            $this->importFabQueue($fabRows);
        });

        $this->info('Legacy CSV import completed.');

        return self::SUCCESS;
    }

    private function importShopRequests(array $rows): void
    {
        foreach ($rows as $row) {
            $customer = $this->resolveCustomer($this->value($row, 'customer'));
            $project = $this->resolveProject(
                $this->value($row, 'job number'),
                $customer->id,
                $this->value($row, 'title'),
                $this->value($row, 'jobsite address')
            );

            $requestedBy = $this->resolveUser($this->value($row, 'created by'));
            $assignedToName = $this->value($row, 'assigned too');
            $assignedTo = $this->resolveUser($assignedToName);

            DrawingRequest::query()->create([
                'request_number' => $this->nextRequestNumber(),
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'requested_by_user_id' => $requestedBy->id,
                'assigned_to_user_id' => $assignedTo?->id,
                'title' => $this->value($row, 'title') ?? 'Imported Drawing Request',
                'description' => null,
                'priority' => 'normal',
                'drawing_type' => $this->mapDrawingType($this->value($row, 'drawing discipline')),
                'job_number' => $this->value($row, 'job number'),
                'customer_address' => $this->value($row, 'jobsite address'),
                'required_date' => null,
                'requested_date' => $this->parseDate($this->value($row, 'modified')) ?? now()->toDateString(),
                'status' => $this->mapRequestStatus($this->value($row, 'status')),
                'notes' => $this->value($row, 'notes'),
                'source_modified_at' => $this->parseDateTime($this->value($row, 'modified')),
                'source_modified_by' => $this->value($row, 'modified by'),
                'source_created_by' => $this->value($row, 'created by'),
                'source_assigned_to' => $assignedToName,
                'source_discipline' => $this->value($row, 'drawing discipline'),
                'source_status' => $this->value($row, 'status'),
                'attachments_count' => (int) ($this->value($row, 'attachments') ?? 0),
                'pointcloud_link' => $this->value($row, 'pointcloud link'),
                'job_link' => $this->value($row, 'job link'),
                'source_image' => $this->value($row, 'image'),
                'import_source' => 'legacy_shop_request',
            ]);
        }
    }

    private function importSubmittals(array $rows): void
    {
        foreach ($rows as $row) {
            $customer = $this->resolveCustomer($this->value($row, 'customer'));
            $project = $this->resolveProject(
                $this->value($row, 'job number'),
                $customer->id,
                $this->value($row, 'title'),
                $this->value($row, 'jobsite address')
            );

            $drawingRequest = $this->resolveDrawingRequestForProject(
                $project->id,
                $customer->id,
                $this->value($row, 'job number'),
                $this->value($row, 'title'),
                $this->value($row, 'jobsite address')
            );

            $submittedBy = $this->resolveUser($this->value($row, 'created by'));

            $returnedStatus = $this->value($row, 'returned status');
            $sourceStatus = $this->value($row, 'purpose');

            DrawingSubmittal::query()->create([
                'submittal_number' => $this->nextSubmittalNumber(),
                'drawing_request_id' => $drawingRequest->id,
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'revision' => 'A',
                'submitted_by_user_id' => $submittedBy->id,
                'submitted_at' => $this->parseDateTime($this->value($row, 'date submitted')),
                'drawing_discipline' => $this->mapSubmittalDiscipline($this->value($row, 'drawing discipline')),
                'purpose' => $this->mapSubmittalPurpose($sourceStatus),
                'status' => $this->mapSubmittalStatus($returnedStatus),
                'approval_received_at' => null,
                'approval_type' => $returnedStatus,
                'approval_notes' => null,
                'next_action' => $this->value($row, 'next action'),
                'notes' => $this->value($row, 'notes'),
                'internal_notes' => null,
                'source_modified_at' => $this->parseDateTime($this->value($row, 'modified')),
                'source_modified_by' => $this->value($row, 'modified by'),
                'source_created_by' => $this->value($row, 'created by'),
                'source_status' => $sourceStatus,
                'returned_status' => $returnedStatus,
                'sent_to_customer' => $this->parseBoolean($this->value($row, 'sent to customer?')),
                'model_link' => $this->value($row, 'model link'),
                'source_image' => $this->value($row, 'image'),
                'mark_to_continue' => $this->parseBoolean($this->value($row, 'mark to continue?')),
                'import_source' => 'legacy_submittal_log',
            ]);
        }
    }

    private function importFabQueue(array $rows): void
    {
        foreach ($rows as $row) {
            $customer = $this->resolveCustomer($this->value($row, 'customer'));
            $project = $this->resolveProject(
                $this->value($row, 'job number'),
                $customer->id,
                $this->value($row, 'title'),
                $this->value($row, 'jobsite address')
            );

            $drawingRequest = $this->resolveDrawingRequestForProject(
                $project->id,
                $customer->id,
                $this->value($row, 'job number'),
                $this->value($row, 'title'),
                $this->value($row, 'jobsite address')
            );

            $submittal = DrawingSubmittal::query()
                ->where('project_id', $project->id)
                ->latest('id')
                ->first();

            if ($submittal === null) {
                $submittedBy = $this->resolveUser($this->value($row, 'modified by'));

                $submittal = DrawingSubmittal::query()->create([
                    'submittal_number' => $this->nextSubmittalNumber(),
                    'drawing_request_id' => $drawingRequest->id,
                    'project_id' => $project->id,
                    'customer_id' => $customer->id,
                    'revision' => 'A',
                    'submitted_by_user_id' => $submittedBy->id,
                    'submitted_at' => null,
                    'drawing_discipline' => $this->mapSubmittalDiscipline($this->value($row, 'drawing discipline')),
                    'purpose' => 'for_construction',
                    'status' => 'submitted',
                    'approval_received_at' => null,
                    'approval_type' => null,
                    'approval_notes' => null,
                    'next_action' => null,
                    'notes' => null,
                    'internal_notes' => null,
                    'import_source' => 'legacy_submittal_log',
                ]);
            }

            $assignedTo = $this->resolveUser($this->value($row, 'modified by'));

            FabQueue::query()->create([
                'submittal_id' => $submittal->id,
                'project_id' => $project->id,
                'queue_number' => $this->nextFabNumber(),
                'priority' => 5,
                'material_requirements' => null,
                'cnc_files_attached' => $this->parseBoolean($this->value($row, 'cnc plate nested?')) ?? false,
                'assigned_to_user_id' => $assignedTo?->id,
                'assigned_at' => $this->parseDateTime($this->value($row, 'modified')),
                'status' => $this->mapFabStatus($this->value($row, 'status')),
                'started_at' => null,
                'completed_at' => null,
                'notes' => $this->value($row, 'notes'),
                'shop_notes' => null,
                'date_released' => $this->parseDate($this->value($row, 'date released')),
                'source_created_at' => $this->parseDateTime($this->value($row, 'created')),
                'source_modified_at' => $this->parseDateTime($this->value($row, 'modified')),
                'source_modified_by' => $this->value($row, 'modified by'),
                'source_status' => $this->value($row, 'status'),
                'attachments_count' => (int) ($this->value($row, 'attachments') ?? 0),
                'model_link' => $this->value($row, '3d cloud model link'),
                'import_source' => 'legacy_fabrication_log',
            ]);
        }
    }

    private function resolveDrawingRequestForProject(
        int $projectId,
        int $customerId,
        ?string $jobNumber,
        ?string $title,
        ?string $address
    ): DrawingRequest {
        $drawingRequest = DrawingRequest::query()
            ->where('project_id', $projectId)
            ->where('job_number', $jobNumber)
            ->latest('id')
            ->first();

        if ($drawingRequest !== null) {
            return $drawingRequest;
        }

        $user = $this->resolveUser(null);

        return DrawingRequest::query()->create([
            'request_number' => $this->nextRequestNumber(),
            'project_id' => $projectId,
            'customer_id' => $customerId,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => null,
            'title' => $title ?? 'Imported Drawing Request',
            'description' => null,
            'priority' => 'normal',
            'drawing_type' => 'other',
            'job_number' => $jobNumber,
            'customer_address' => $address,
            'required_date' => null,
            'requested_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => null,
            'import_source' => 'legacy_shop_request',
        ]);
    }

    private function resolveCustomer(?string $name): Customer
    {
        $customerName = $name ?: 'Imported Customer';

        $existing = Customer::query()
            ->whereRaw('lower(name) = ?', [Str::lower($customerName)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Customer::query()->create([
            'name' => $customerName,
            'active' => true,
        ]);
    }

    private function resolveProject(?string $jobNumber, int $customerId, ?string $title, ?string $address): Project
    {
        $projectNumber = $this->normalizeProjectNumber($jobNumber, $customerId, $title, $address);

        $project = Project::query()->where('project_number', $projectNumber)->first();

        if ($project !== null) {
            return $project;
        }

        return Project::query()->create([
            'project_number' => $projectNumber,
            'name' => $title ?: $projectNumber,
            'customer_id' => $customerId,
            'address' => $address,
            'status' => 'active',
        ]);
    }

    private function resolveUser(?string $name): ?User
    {
        if ($name === null || trim($name) === '') {
            return User::query()->first();
        }

        $cleanName = trim($name);

        $user = User::query()
            ->whereRaw('lower(name) = ?', [Str::lower($cleanName)])
            ->first();

        if ($user !== null) {
            return $user;
        }

        $emailPrefix = Str::slug($cleanName);

        if ($emailPrefix === '') {
            $emailPrefix = 'imported-user';
        }

        $email = $emailPrefix.'@import.local';
        $suffix = 1;

        while (User::query()->where('email', $email)->exists()) {
            $suffix++;
            $email = $emailPrefix.$suffix.'@import.local';
        }

        return User::query()->create([
            'name' => $cleanName,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'role' => 'viewer',
            'active' => true,
        ]);
    }

    private function readCsv(string $path): array
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("CSV file not found: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headers = null;
        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $row);

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $normalized = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $normalized[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;

        return Str::lower(trim($header));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function value(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseBoolean(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::lower(trim($value));

        if (in_array($normalized, ['true', '1', 'yes'], true)) {
            return true;
        }

        if (in_array($normalized, ['false', '0', 'no'], true)) {
            return false;
        }

        return null;
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(?string $value): ?string
    {
        $dateTime = $this->parseDateTime($value);

        return $dateTime?->toDateString();
    }

    private function normalizeProjectNumber(?string $jobNumber, int $customerId, ?string $title, ?string $address): string
    {
        if ($jobNumber !== null && trim($jobNumber) !== '') {
            return Str::upper(trim($jobNumber));
        }

        $hash = substr(md5($customerId.'|'.$title.'|'.$address), 0, 12);

        return 'IMP-'.$hash;
    }

    private function nextRequestNumber(): string
    {
        $number = str_pad((string) $this->requestSequence, 4, '0', STR_PAD_LEFT);
        $this->requestSequence++;

        return "DR-{$this->runToken}-{$number}";
    }

    private function nextSubmittalNumber(): string
    {
        $number = str_pad((string) $this->submittalSequence, 4, '0', STR_PAD_LEFT);
        $this->submittalSequence++;

        return "SUB-{$this->runToken}-{$number}";
    }

    private function nextFabNumber(): string
    {
        $number = str_pad((string) $this->fabSequence, 4, '0', STR_PAD_LEFT);
        $this->fabSequence++;

        return "FAB-{$this->runToken}-{$number}";
    }

    private function mapDrawingType(?string $discipline): string
    {
        $value = Str::lower($discipline ?? '');

        if (Str::contains($value, 'rail')) {
            return 'railings';
        }

        if (Str::contains($value, 'stair')) {
            return 'stairs';
        }

        if (Str::contains($value, 'handrail')) {
            return 'handrail';
        }

        if (Str::contains($value, 'canopy')) {
            return 'canopy';
        }

        if (Str::contains($value, 'steel') || Str::contains($value, 'structural')) {
            return 'structural';
        }

        if (Str::contains($value, 'misc') || Str::contains($value, 'lintel') || Str::contains($value, 'embed') || Str::contains($value, 'joist') || Str::contains($value, 'deck')) {
            return 'misc';
        }

        return 'other';
    }

    private function mapRequestStatus(?string $status): string
    {
        $value = Str::upper(trim($status ?? ''));

        return match ($value) {
            'NOT STARTED' => 'pending',
            'IN PROGRESS' => 'in_progress',
            'DRAWINGS COMPLETE' => 'ready_to_submit',
            'SUBMITTED' => 'submitted',
            'HOLD' => 'on_hold',
            'DONE' => 'approved',
            default => 'pending',
        };
    }

    private function mapSubmittalDiscipline(?string $discipline): string
    {
        $value = Str::lower($discipline ?? '');

        if (Str::contains($value, 'residential')) {
            return 'residential_steel';
        }

        if (Str::contains($value, 'commercial steel') || Str::contains($value, 'commercial structural')) {
            return 'commercial_structural';
        }

        if (Str::contains($value, 'commercial misc') || Str::contains($value, 'misc') || Str::contains($value, 'gate') || Str::contains($value, 'lintel') || Str::contains($value, 'embed')) {
            return 'commercial_misc';
        }

        if (Str::contains($value, 'rail')) {
            return 'railings';
        }

        if (Str::contains($value, 'stair')) {
            return 'stairs';
        }

        return 'other';
    }

    private function mapSubmittalPurpose(?string $purpose): string
    {
        $value = Str::upper(trim($purpose ?? ''));

        if (Str::contains($value, 'FOR INFORMATION')) {
            return 'for_information';
        }

        if (Str::contains($value, 'FOR FIELD USE') || Str::contains($value, 'RELEASED FOR FABRICATION') || Str::contains($value, 'FABRICATED')) {
            return 'for_construction';
        }

        if (Str::contains($value, 'RESUBMIT')) {
            return 'resubmittal';
        }

        return 'for_approval';
    }

    private function mapSubmittalStatus(?string $returnedStatus): string
    {
        $value = Str::upper(trim($returnedStatus ?? ''));

        if (Str::contains($value, 'APPROVED AS NOTED')) {
            return 'approved_as_noted';
        }

        if (Str::contains($value, 'APPROVED')) {
            return 'approved';
        }

        if (Str::contains($value, 'REVISE')) {
            return 'revise_and_resubmit';
        }

        if (Str::contains($value, 'REJECT')) {
            return 'rejected';
        }

        if (Str::contains($value, 'FIELD')) {
            return 'field_verify_required';
        }

        return 'submitted';
    }

    private function mapFabStatus(?string $status): string
    {
        $value = Str::upper(trim($status ?? ''));

        if (Str::contains($value, 'HOLD')) {
            return 'on_hold';
        }

        if (Str::contains($value, 'IN PROGRESS')) {
            return 'in_progress';
        }

        if (Str::contains($value, 'COMPLETE') || Str::contains($value, 'DONE')) {
            return 'completed';
        }

        return 'queued';
    }
}
