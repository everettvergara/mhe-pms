<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        ?User $user,
        string $module,
        string $action,
        ?int $recordId = null,
        ?string $description = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'module' => $module,
            'action' => $action,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
