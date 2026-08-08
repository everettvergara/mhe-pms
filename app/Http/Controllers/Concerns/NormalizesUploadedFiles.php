<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;

trait NormalizesUploadedFiles
{
    /**
     * @return array<int, UploadedFile>
     */
    protected function normalizeUploadedFiles(mixed $files): array
    {
        if ($files === null || $files === []) {
            return [];
        }

        $files = is_array($files) ? $files : [$files];

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
    }
}
