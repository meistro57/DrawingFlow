<?php

namespace App\Http\Controllers;

use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $projects = Project::query()
            ->with('customer:id,name')
            ->where(function ($q) use ($query): void {
                $q->where('project_number', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Project $project): array => [
                'type' => 'project',
                'title' => $project->project_number.' · '.$project->name,
                'subtitle' => $project->customer?->name,
                'url' => route('projects.show', $project),
            ]);

        $requests = DrawingRequest::query()
            ->with('project:id,project_number,name')
            ->where(function ($q) use ($query): void {
                $q->where('request_number', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%")
                    ->orWhere('job_number', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (DrawingRequest $drawingRequest): array => [
                'type' => 'drawing_request',
                'title' => $drawingRequest->request_number.' · '.$drawingRequest->title,
                'subtitle' => $drawingRequest->project?->project_number,
                'url' => route('drawing-requests.show', $drawingRequest),
            ]);

        $submittals = DrawingSubmittal::query()
            ->with('project:id,project_number,name')
            ->where(function ($q) use ($query): void {
                $q->where('submittal_number', 'like', "%{$query}%")
                    ->orWhere('revision', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (DrawingSubmittal $submittal): array => [
                'type' => 'submittal',
                'title' => $submittal->submittal_number.' · Rev '.$submittal->revision,
                'subtitle' => $submittal->project?->project_number,
                'url' => route('submittals.show', $submittal),
            ]);

        return response()->json([
            'data' => $projects
                ->concat($requests)
                ->concat($submittals)
                ->take(12)
                ->values(),
        ]);
    }
}
