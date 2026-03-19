<?php

use App\Http\Controllers\Admin\BoostController;
use App\Http\Controllers\Admin\DataBackupController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrawingRequestController;
use App\Http\Controllers\FabQueueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubmittalController;
use App\Http\Controllers\SubmittalFileController;
use App\Http\Controllers\SubmittalNoteController;
use App\Http\Controllers\SubmittalPdfMarkupController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('customers/import/template', [CustomerController::class, 'downloadImportTemplate'])->name('customers.import.template');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::get('projects/{project}/attachments/{attachment}/view', [ProjectAttachmentController::class, 'view'])->name('projects.attachments.view');
    Route::get('projects/{project}/attachments/{attachment}/download', [ProjectAttachmentController::class, 'download'])->name('projects.attachments.download');
    Route::delete('projects/{project}/attachments/{attachment}', [ProjectAttachmentController::class, 'destroy'])->name('projects.attachments.destroy');

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
    Route::patch('submittals/{submittal}/purpose', [SubmittalController::class, 'updatePurpose'])->name('submittals.purpose.update');
    Route::post('submittals/{submittal}/submit', [SubmittalController::class, 'submit'])->name('submittals.submit');
    Route::post('submittals/{submittal}/process-approval', [SubmittalController::class, 'processApproval'])->name('submittals.process-approval');
    Route::post('submittals/{submittal}/create-revision', [SubmittalController::class, 'createRevision'])->name('submittals.create-revision');
    Route::post('submittals/{submittal}/files', [SubmittalFileController::class, 'store'])->name('submittals.files.store');
    Route::get('submittals/{submittal}/files/{submittalFile}/view', [SubmittalFileController::class, 'view'])->name('submittals.files.view');
    Route::get('submittals/{submittal}/files/{submittalFile}/download', [SubmittalFileController::class, 'download'])->name('submittals.files.download');
    Route::get('submittals/{submittal}/files/{submittalFile}/markups', [SubmittalPdfMarkupController::class, 'index'])->name('submittals.files.markups.index');
    Route::post('submittals/{submittal}/files/{submittalFile}/markups', [SubmittalPdfMarkupController::class, 'store'])->name('submittals.files.markups.store');
    Route::post('submittals/{submittal}/files/{submittalFile}/markups/import', [SubmittalPdfMarkupController::class, 'import'])->name('submittals.files.markups.import');
    Route::put('submittals/{submittal}/files/{submittalFile}/markups/{markup}', [SubmittalPdfMarkupController::class, 'update'])->name('submittals.files.markups.update');
    Route::delete('submittals/{submittal}/files/{submittalFile}/markups/{markup}', [SubmittalPdfMarkupController::class, 'destroy'])->name('submittals.files.markups.destroy');
    Route::get('submittals/{submittal}/files/{submittalFile}/page-scales', [SubmittalPdfMarkupController::class, 'scaleIndex'])->name('submittals.files.page-scales.index');
    Route::put('submittals/{submittal}/files/{submittalFile}/page-scales/{pageNumber}', [SubmittalPdfMarkupController::class, 'scaleUpsert'])->name('submittals.files.page-scales.upsert');
    Route::delete('submittals/{submittal}/files/{submittalFile}/page-scales/{pageNumber}', [SubmittalPdfMarkupController::class, 'scaleDestroy'])->name('submittals.files.page-scales.destroy');
    Route::get('submittals/{submittal}/files/{submittalFile}/markups/export', [SubmittalPdfMarkupController::class, 'export'])->name('submittals.files.markups.export');
    Route::get('submittals/{submittal}/notes', [SubmittalNoteController::class, 'index'])->name('submittals.notes.index');
    Route::post('submittals/{submittal}/notes', [SubmittalNoteController::class, 'store'])->name('submittals.notes.store');

    // Fab Queue
    Route::get('fab-queue', [FabQueueController::class, 'index'])->name('fab-queue.index');
    Route::get('fab-queue/{fab_queue}', [FabQueueController::class, 'show'])->name('fab-queue.show');
    Route::post('fab-queue/{fab_queue}/assign', [FabQueueController::class, 'assign'])->name('fab-queue.assign');
    Route::post('fab-queue/{fab_queue}/complete', [FabQueueController::class, 'complete'])->name('fab-queue.complete');
    Route::put('fab-queue/{fab_queue}/notes', [FabQueueController::class, 'updateNotes'])->name('fab-queue.update-notes');

    // Admin
    Route::middleware('can:admin-access')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::get('boost', [BoostController::class, 'index'])->name('boost.index');
        Route::get('boost/mcp-status', [BoostController::class, 'mcpStatus'])->name('boost.mcp-status');
        Route::get('boost/browser-logs', [BoostController::class, 'browserLogs'])->name('boost.browser-logs');
        Route::delete('boost/browser-logs', [BoostController::class, 'clearBrowserLogs'])->name('boost.browser-logs.clear');

        Route::get('backups', [DataBackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [DataBackupController::class, 'store'])->name('backups.store');
        Route::post('backups/restore', [DataBackupController::class, 'restore'])->name('backups.restore');
        Route::get('backups/{fileName}', [DataBackupController::class, 'download'])->name('backups.download');
    });
});
