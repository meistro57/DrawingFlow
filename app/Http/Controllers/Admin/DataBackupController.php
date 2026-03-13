<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Services\DataBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataBackupController extends Controller
{
    public function __construct(private DataBackupService $dataBackupService) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Backups/Index', [
            'backups' => collect($this->dataBackupService->listBackups())
                ->map(fn (array $backup): array => [
                    ...$backup,
                    'download_url' => route('admin.backups.download', ['fileName' => $backup['name']]),
                ])
                ->all(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->dataBackupService->createBackup();

        return back()->with('success', 'Backup created successfully.');
    }

    public function restore(RestoreBackupRequest $request): RedirectResponse
    {
        try {
            $this->dataBackupService->restoreFromUpload($request->file('backup_file'));
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Restore failed. '.$throwable->getMessage());
        }

        return back()->with('success', 'Backup restored successfully.');
    }

    public function download(string $fileName): StreamedResponse
    {
        $path = $this->dataBackupService->backupPathFromName($fileName);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
