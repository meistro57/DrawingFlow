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

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'active_projects' => Project::active()->count(),
                'pending_requests' => DrawingRequest::status('pending')->count(),
                'in_progress_requests' => DrawingRequest::status('in_progress')->count(),
                'awaiting_approval' => DrawingSubmittal::status('submitted')->count(),
                'fab_queue_count' => FabQueue::queued()->count(),
            ],
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
