<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Customer;
use App\Models\Project;
use App\Services\ProjectAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with('customer')
            ->withCount(['drawingRequests', 'submittals'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name']),
            'preselected_customer_id' => request('customer_id'),
        ]);
    }

    public function store(ProjectRequest $request, ProjectAttachmentService $projectAttachmentService): RedirectResponse
    {
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);

        $project = DB::transaction(function () use ($validated, $attachments, $projectAttachmentService, $request): Project {
            unset($validated['attachments']);
            $project = Project::create($validated);

            if ($attachments !== []) {
                $projectAttachmentService->storeUploadedFiles($project, $attachments, (int) $request->user()->id);
            }

            return $project;
        });

        return redirect()->route('projects.index')
            ->with('success', $attachments === []
                ? 'Project created successfully.'
                : "Project created successfully with {$project->attachments()->count()} attachment(s).");
    }

    public function show(Project $project): Response
    {
        $project->load([
            'customer',
            'drawingRequests' => fn ($q) => $q->with('assignedTo')->latest()->take(10),
            'attachments' => fn ($q) => $q->with('uploadedBy:id,name')->latest(),
        ]);
        $project->loadCount(['drawingRequests', 'submittals', 'fabQueueEntries']);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProjectRequest $request, Project $project, ProjectAttachmentService $projectAttachmentService): RedirectResponse
    {
        $validated = $request->validated();
        $attachments = $request->file('attachments', []);

        DB::transaction(function () use ($project, $validated, $attachments, $projectAttachmentService, $request): void {
            unset($validated['attachments']);
            $project->update($validated);

            if ($attachments !== []) {
                $projectAttachmentService->storeUploadedFiles($project, $attachments, (int) $request->user()->id);
            }
        });

        return redirect()->route('projects.show', $project)
            ->with('success', $attachments === []
                ? 'Project updated successfully.'
                : 'Project updated successfully and added '.count($attachments).' attachment(s).');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
