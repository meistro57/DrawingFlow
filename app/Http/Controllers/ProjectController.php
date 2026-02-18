<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
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
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
            'preselected_customer_id' => request('customer_id'),
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project): Response
    {
        $project->load(['customer', 'drawingRequests' => fn ($q) => $q->with('assignedTo')->latest()->take(10)]);
        $project->loadCount(['drawingRequests', 'submittals', 'fabQueueEntries']);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
