<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
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
        $this->assertCanManageAttachments($attachable);

        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($files === []) {
            throw new InvalidArgumentException('At least one file is required.');
        }

        $maxPerRecord = $this->maxPerRecord($attachable);
        $existingCount = $attachable->attachments()->count();

        if ($existingCount + count($files) > $maxPerRecord) {
            throw new InvalidArgumentException("A maximum of {$maxPerRecord} files is allowed per record.");
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
        $attachable = $attachment->attachable;

        if ($attachable === null) {
            throw new InvalidArgumentException('Unsupported attachment.');
        }

        $this->assertCanManageAttachments($attachable);

        DB::transaction(function () use ($attachment): void {
            $this->deleteFile($attachment);
            $attachment->delete();
        });
    }

    protected function assertCanManageAttachments(Model $attachable): void
    {
        if ($attachable instanceof PmsHeader && ! $attachable->isDraft()) {
            throw new RuntimeException('Attachments can only be added to draft PMS records.');
        }

        if ($attachable instanceof PmsDetail && ! $attachable->pmsHeader->isDraft()) {
            throw new RuntimeException('Attachments can only be added to draft PMS records.');
        }

        if ($attachable instanceof MheDowntime && ! $attachable->isDraft()) {
            throw new RuntimeException('Attachments can only be added to draft downtime records.');
        }
    }

    protected function maxPerRecord(Model $attachable): int
    {
        if ($attachable instanceof MheDowntime || $attachable instanceof MheDowntimeActionPlan) {
            return (int) config('mhe_downtime.attachments.max_per_record', 10);
        }

        return (int) config('pms.attachments.max_per_record', 10);
    }

    protected function storageDirectory(Model $attachable): string
    {
        if ($attachable instanceof PmsHeader) {
            return 'pms-attachments/'.$attachable->id;
        }

        if ($attachable instanceof PmsDetail) {
            return 'pms-detail-attachments/'.$attachable->id;
        }

        if ($attachable instanceof MheDowntime) {
            return 'mhe-downtime-attachments/'.$attachable->id;
        }

        if ($attachable instanceof MheDowntimeActionPlan) {
            return 'mhe-downtime-action-plan-attachments/'.$attachable->id;
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
