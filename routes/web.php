<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrawingRequestController;
use App\Http\Controllers\FabQueueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubmittalController;
use App\Http\Controllers\SubmittalFileController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Customers
    Route::resource('customers', CustomerController::class);

    // Projects
    Route::resource('projects', ProjectController::class);

    // Drawing Requests
    Route::resource('drawing-requests', DrawingRequestController::class);
    Route::post('drawing-requests/{drawing_request}/assign', [DrawingRequestController::class, 'assign'])->name('drawing-requests.assign');
    Route::post('drawing-requests/{drawing_request}/mark-ready', [DrawingRequestController::class, 'markReady'])->name('drawing-requests.mark-ready');
    Route::post('drawing-requests/{drawing_request}/cancel', [DrawingRequestController::class, 'cancel'])->name('drawing-requests.cancel');
    Route::post('drawing-requests/{drawing_request}/hold', [DrawingRequestController::class, 'hold'])->name('drawing-requests.hold');

    // Submittals
    Route::get('submittals', [SubmittalController::class, 'index'])->name('submittals.index');
    Route::get('submittals/{submittal}', [SubmittalController::class, 'show'])->name('submittals.show');
    Route::delete('submittals/{submittal}', [SubmittalController::class, 'destroy'])->name('submittals.destroy');
    Route::post('submittals/from-request/{drawing_request}', [SubmittalController::class, 'createFromRequest'])->name('submittals.create-from-request');
    Route::post('submittals/{submittal}/submit', [SubmittalController::class, 'submit'])->name('submittals.submit');
    Route::post('submittals/{submittal}/process-approval', [SubmittalController::class, 'processApproval'])->name('submittals.process-approval');
    Route::post('submittals/{submittal}/create-revision', [SubmittalController::class, 'createRevision'])->name('submittals.create-revision');

    // Submittal Files
    Route::post('submittals/{submittal}/files', [SubmittalFileController::class, 'store'])->name('submittal-files.store');
    Route::get('submittal-files/{submittal_file}/download', [SubmittalFileController::class, 'download'])->name('submittal-files.download');
    Route::delete('submittal-files/{submittal_file}', [SubmittalFileController::class, 'destroy'])->name('submittal-files.destroy');

    // Fab Queue
    Route::get('fab-queue', [FabQueueController::class, 'index'])->name('fab-queue.index');
    Route::get('fab-queue/{fab_queue}', [FabQueueController::class, 'show'])->name('fab-queue.show');
    Route::post('fab-queue/{fab_queue}/assign', [FabQueueController::class, 'assign'])->name('fab-queue.assign');
    Route::post('fab-queue/{fab_queue}/complete', [FabQueueController::class, 'complete'])->name('fab-queue.complete');
    Route::put('fab-queue/{fab_queue}/notes', [FabQueueController::class, 'updateNotes'])->name('fab-queue.update-notes');
});
