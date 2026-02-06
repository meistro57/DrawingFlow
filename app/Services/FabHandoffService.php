<?php

namespace App\Services;

use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FabHandoffService
{
    /**
     * Create a fab queue entry from an approved submittal.
     */
    public function createFabQueueEntry(DrawingSubmittal $submittal): FabQueue
    {
        if (! $submittal->isApproved()) {
            throw new \InvalidArgumentException('Submittal must be approved to create a fab queue entry.');
        }

        return DB::transaction(function () use ($submittal) {
            $queueNumber = $this->generateQueueNumber();

            return FabQueue::create([
                'submittal_id' => $submittal->id,
                'project_id' => $submittal->project_id,
                'queue_number' => $queueNumber,
                'priority' => $this->calculatePriority($submittal),
                'status' => 'queued',
            ]);
        });
    }

    /**
     * Assign a fab queue entry to a fabricator.
     */
    public function assignToFabricator(FabQueue $entry, User $user): void
    {
        $entry->update([
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark a fab queue entry as completed.
     */
    public function complete(FabQueue $entry): void
    {
        $entry->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Calculate priority based on submittal context.
     */
    private function calculatePriority(DrawingSubmittal $submittal): int
    {
        $request = $submittal->drawingRequest;

        return match ($request->priority) {
            'urgent' => 1,
            'high' => 3,
            'normal' => 5,
            'low' => 8,
            default => 5,
        };
    }

    /**
     * Generate a unique queue number.
     */
    private function generateQueueNumber(): string
    {
        $year = date('Y');
        $lastEntry = FabQueue::where('queue_number', 'like', "FAB-{$year}-%")
            ->orderByDesc('queue_number')
            ->first();

        if ($lastEntry) {
            $lastNumber = (int) substr($lastEntry->queue_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('FAB-%s-%04d', $year, $nextNumber);
    }
}
