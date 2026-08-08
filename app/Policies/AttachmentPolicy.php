<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        $pmsHeader = $attachment->resolvePmsHeader();

        if ($pmsHeader !== null) {
            return $user->can('view', $pmsHeader);
        }

        $downtime = $attachment->resolveMheDowntime();

        return $downtime !== null && $user->can('view', $downtime);
    }

    public function create(User $user, Attachment $attachment): bool
    {
        return $this->canManageAttachment($user, $attachment);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $this->canManageAttachment($user, $attachment);
    }

    protected function canManageAttachment(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof PmsHeader) {
            return $attachable->isDraft() && $user->can('update', $attachable);
        }

        if ($attachable instanceof PmsDetail) {
            return $attachable->pmsHeader->isDraft() && $user->can('update', $attachable->pmsHeader);
        }

        if ($attachable instanceof MheDowntime) {
            return $attachable->isDraft() && $user->can('update', $attachable);
        }

        if ($attachable instanceof MheDowntimeActionPlan) {
            return $user->can('update', $attachable);
        }

        return false;
    }
}
