<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        $pmsHeader = $attachment->resolvePmsHeader();

        return $pmsHeader !== null && $user->can('view', $pmsHeader);
    }

    public function create(User $user, Attachment $attachment): bool
    {
        return $this->canManageDraftAttachment($user, $attachment);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $this->canManageDraftAttachment($user, $attachment);
    }

    protected function canManageDraftAttachment(User $user, Attachment $attachment): bool
    {
        $pmsHeader = $attachment->resolvePmsHeader();

        return $pmsHeader !== null
            && $pmsHeader->isDraft()
            && $user->can('update', $pmsHeader);
    }
}
