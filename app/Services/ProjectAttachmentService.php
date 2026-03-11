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
            $storedPath = $file->store("project-attachments/{$project->id}");

            ProjectAttachment::create([
                'project_id' => $project->id,
                'filename' => basename($storedPath),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by_user_id' => $uploadedByUserId,
                'uploaded_at' => now(),
            ]);
        }
    }
}
