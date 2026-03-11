<?php

namespace App\Http\Controllers;

use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\FabQueue;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $queueFilter = request()->string('queue_filter')->value() ?: 'all';
        $myRequestStatuses = ['pending', 'in_progress', 'ready_to_submit'];
        $mySubmittalStatuses = ['draft', 'ready_to_submit', 'revise_and_resubmit', 'field_verify_required'];
        $myFabStatuses = ['queued', 'in_progress', 'on_hold'];
        $allowedQueueFilters = ['all', 'overdue', 'due_soon', 'high_priority'];

        if (! in_array($queueFilter, $allowedQueueFilters, true)) {
            $queueFilter = 'all';
        }

        $myQueueBaseQuery = DrawingRequest::query()
            ->with(['project:id,name', 'customer:id,name', 'assignedTo:id,name'])
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status', $myRequestStatuses);

        $myQueue = (clone $myQueueBaseQuery)
            ->when($queueFilter === 'overdue', function ($query): void {
                $query->whereDate('required_date', '<', now()->toDateString());
            })
            ->when($queueFilter === 'due_soon', function ($query): void {
                $query->whereBetween('required_date', [
                    now()->toDateString(),
                    now()->copy()->addDays(7)->toDateString(),
                ]);
            })
            ->when($queueFilter === 'high_priority', function ($query): void {
                $query->whereIn('priority', ['urgent', 'high']);
            })
            ->orderByRaw("case priority when 'urgent' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderBy('required_date')
            ->limit(6)
            ->get();

        $queueFilterCounts = [
            'all' => (clone $myQueueBaseQuery)->count(),
            'overdue' => (clone $myQueueBaseQuery)
                ->whereDate('required_date', '<', now()->toDateString())
                ->count(),
            'due_soon' => (clone $myQueueBaseQuery)
                ->whereBetween('required_date', [
                    now()->toDateString(),
                    now()->copy()->addDays(7)->toDateString(),
                ])
                ->count(),
            'high_priority' => (clone $myQueueBaseQuery)
                ->whereIn('priority', ['urgent', 'high'])
                ->count(),
        ];

        $mySubmittalWork = DrawingSubmittal::with(['project:id,name', 'customer:id,name', 'drawingRequest:id,title'])
            ->where('submitted_by_user_id', $user->id)
            ->whereIn('status', $mySubmittalStatuses)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $myFabAssignments = FabQueue::with(['project:id,name', 'submittal:id,submittal_number,revision'])
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status', $myFabStatuses)
            ->orderBy('priority')
            ->orderBy('created_at')
            ->limit(6)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'active_projects' => Project::active()->count(),
                'pending_requests' => DrawingRequest::status('pending')->count(),
                'in_progress_requests' => DrawingRequest::status('in_progress')->count(),
                'awaiting_approval' => DrawingSubmittal::status('submitted')->count(),
                'fab_queue_count' => FabQueue::queued()->count(),
            ],
            'my_workload' => [
                'assigned_requests' => $queueFilterCounts['all'],
                'submittals_needing_action' => DrawingSubmittal::query()
                    ->where('submitted_by_user_id', $user->id)
                    ->whereIn('status', $mySubmittalStatuses)
                    ->count(),
                'fab_assignments' => FabQueue::query()
                    ->where('assigned_to_user_id', $user->id)
                    ->whereIn('status', $myFabStatuses)
                    ->count(),
                'due_this_week' => DrawingRequest::query()
                    ->where('assigned_to_user_id', $user->id)
                    ->whereIn('status', $myRequestStatuses)
                    ->whereBetween('required_date', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
                    ->count(),
            ],
            'my_queue_filter' => $queueFilter,
            'my_queue_filter_counts' => $queueFilterCounts,
            'my_queue' => $myQueue,
            'my_submittal_work' => $mySubmittalWork,
            'my_fab_assignments' => $myFabAssignments,
            'recent_requests' => DrawingRequest::with(['project', 'customer', 'assignedTo'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_submittals' => DrawingSubmittal::with(['project', 'customer', 'submittedBy'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
