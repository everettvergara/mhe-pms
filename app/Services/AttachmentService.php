<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class AttachmentService
{
    /**
     * @return Collection<int, Attachment>
     */
    public function store(Model $attachable, User $user, UploadedFile $file): Collection
    {
        return $this->storeMany($attachable, $user, [$file]);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, Attachment>
     */
    public function storeMany(Model $attachable, User $user, array $files): Collection
    {
        $pmsHeader = $this->resolvePmsHeader($attachable);

        if ($pmsHeader === null) {
            throw new InvalidArgumentException('Unsupported attachable type.');
        }

        if (! $pmsHeader->isDraft()) {
            throw new RuntimeException('Attachments can only be added to draft PMS records.');
        }

        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($files === []) {
            throw new InvalidArgumentException('At least one file is required.');
        }

        $maxPerRecord = (int) config('pms.attachments.max_per_record', 10);
        $existingCount = $attachable->attachments()->count();

        if ($existingCount + count($files) > $maxPerRecord) {
            throw new InvalidArgumentException("A maximum of {$maxPerRecord} photos is allowed per record.");
        }

        $storageDirectory = $this->storageDirectory($attachable);

        return DB::transaction(function () use ($attachable, $user, $files, $storageDirectory): Collection {
            $attachments = collect();

            foreach ($files as $file) {
                $path = $file->store($storageDirectory, 'public');

                $attachments->push($attachable->attachments()->create([
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'created_by' => $user->id,
                ]));
            }

            return $attachments;
        });
    }

    public function destroy(Attachment $attachment, User $user): void
    {
        $pmsHeader = $attachment->resolvePmsHeader();

        if ($pmsHeader === null) {
            throw new InvalidArgumentException('Unsupported attachment.');
        }

        if (! $pmsHeader->isDraft()) {
            throw new RuntimeException('Attachments can only be removed from draft PMS records.');
        }

        DB::transaction(function () use ($attachment): void {
            $this->deleteFile($attachment);
            $attachment->delete();
        });
    }

    protected function resolvePmsHeader(Model $attachable): ?PmsHeader
    {
        if ($attachable instanceof PmsHeader) {
            return $attachable;
        }

        if ($attachable instanceof PmsDetail) {
            return $attachable->pmsHeader;
        }

        return null;
    }

    protected function storageDirectory(Model $attachable): string
    {
        if ($attachable instanceof PmsHeader) {
            return 'pms-attachments/'.$attachable->id;
        }

        if ($attachable instanceof PmsDetail) {
            return 'pms-detail-attachments/'.$attachable->id;
        }

        throw new InvalidArgumentException('Unsupported attachable type.');
    }

    protected function deleteFile(Attachment $attachment): void
    {
        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
    }
}
