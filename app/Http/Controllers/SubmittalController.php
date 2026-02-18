<?php

namespace App\Http\Controllers;

use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Services\FabHandoffService;
use App\Services\SubmittalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmittalController extends Controller
{
    public function __construct(
        private SubmittalService $service,
        private FabHandoffService $fabService,
    ) {}

    public function index(): Response
    {
        $submittals = DrawingSubmittal::with(['project', 'customer', 'submittedBy', 'drawingRequest'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Submittals/Index', [
            'submittals' => $submittals,
        ]);
    }

    public function show(DrawingSubmittal $submittal): Response
    {
        $submittal->load([
            'project',
            'customer',
            'submittedBy',
            'drawingRequest',
            'approvals' => fn ($q) => $q->with('createdBy')->latest(),
            'fabQueueEntry.assignedTo',
        ]);

        return Inertia::render('Submittals/Show', [
            'submittal' => $submittal,
        ]);
    }

    public function createFromRequest(DrawingRequest $drawingRequest): RedirectResponse
    {
        $submittal = $this->service->createFromRequest($drawingRequest, auth()->id());

        return redirect()->route('submittals.show', $submittal)
            ->with('success', 'Submittal created from drawing request.');
    }

    public function submit(DrawingSubmittal $submittal): RedirectResponse
    {
        $this->service->submit($submittal);

        return back()->with('success', 'Submittal has been submitted for approval.');
    }

    public function processApproval(Request $request, DrawingSubmittal $submittal): RedirectResponse
    {
        $validated = $request->validate([
            'approval_type' => 'required|in:approved,approved_as_noted,revise_and_resubmit,rejected,field_verify_required',
            'reviewer_name' => 'nullable|string|max:255',
            'reviewer_title' => 'nullable|string|max:255',
            'reviewer_company' => 'nullable|string|max:255',
            'reviewer_email' => 'nullable|email|max:255',
            'approval_notes' => 'nullable|string',
            'conditions' => 'nullable|string',
        ]);

        $validated['created_by_user_id'] = auth()->id();

        $this->service->processApproval($submittal, $validated['approval_type'], $validated);

        // Auto-create fab queue entry if approved
        if (in_array($validated['approval_type'], ['approved', 'approved_as_noted'])) {
            $submittal->refresh();
            $this->fabService->createFabQueueEntry($submittal);
        }

        return back()->with('success', 'Approval processed successfully.');
    }

    public function createRevision(DrawingSubmittal $submittal): RedirectResponse
    {
        $newSubmittal = $this->service->createRevision($submittal);

        return redirect()->route('submittals.show', $newSubmittal)
            ->with('success', "Revision {$newSubmittal->revision} created.");
    }

    public function destroy(DrawingSubmittal $submittal): RedirectResponse
    {
        $submittal->delete();

        return redirect()->route('submittals.index')
            ->with('success', 'Submittal deleted successfully.');
    }
}
