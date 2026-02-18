<?php

namespace App\Http\Controllers;

use App\Models\FabQueue;
use App\Models\User;
use App\Services\FabHandoffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FabQueueController extends Controller
{
    public function __construct(
        private FabHandoffService $service
    ) {}

    public function index(): Response
    {
        $entries = FabQueue::with(['submittal.drawingRequest', 'project', 'assignedTo'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->paginate(15);

        return Inertia::render('FabQueue/Index', [
            'entries' => $entries,
            'users' => User::where('active', true)->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function show(FabQueue $fabQueue): Response
    {
        $fabQueue->load(['submittal.drawingRequest', 'submittal.customer', 'project', 'assignedTo']);

        return Inertia::render('FabQueue/Show', [
            'entry' => $fabQueue,
            'users' => User::where('active', true)->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function assign(Request $request, FabQueue $fabQueue): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $this->service->assignToFabricator($fabQueue, $user);

        return back()->with('success', "Assigned to {$user->name}.");
    }

    public function complete(FabQueue $fabQueue): RedirectResponse
    {
        $this->service->complete($fabQueue);

        return back()->with('success', 'Fab queue entry marked as completed.');
    }

    public function updateNotes(Request $request, FabQueue $fabQueue): RedirectResponse
    {
        $validated = $request->validate([
            'shop_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $fabQueue->update($validated);

        return back()->with('success', 'Notes updated.');
    }
}
