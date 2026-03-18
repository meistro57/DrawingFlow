<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAttachment;
use Illuminate\Http\UploadedFile;

class ProjectAttachmentService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeUploadedFiles(Project $project, array $files, int $uploadedByUserId): void
    {
        foreach ($files as $file) {
            $documentKey = $this->buildDocumentKey($file->getClientOriginalName());

            $latestVersion = $project->attachments()
                ->forDocumentKey($documentKey)
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            $nextVersionNumber = $latestVersion === null ? 1 : $latestVersion->version_number + 1;

            if ($latestVersion !== null) {
                $latestVersion->update(['is_latest' => false]);
            }

            $storedPath = $file->store("project-attachments/{$project->id}");

            ProjectAttachment::create([
                'project_id' => $project->id,
                'filename' => basename($storedPath),
                'original_filename' => $file->getClientOriginalName(),
                'document_key' => $documentKey,
                'version_number' => $nextVersionNumber,
                'is_latest' => true,
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by_user_id' => $uploadedByUserId,
                'uploaded_at' => now(),
            ]);
        }
    }

    private function buildDocumentKey(string $originalFilename): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($originalFilename));

        return strtolower($normalized === '' ? 'attachment' : $normalized);
    }
}
