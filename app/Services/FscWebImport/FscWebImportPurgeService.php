<?php

namespace App\Services\FscWebImport;

use App\Models\Attachment;
use App\Models\EagleEyeImportLog;
use App\Models\EagleEyeImportMap;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\SupplierSite;
use App\Models\User;
use App\Models\UserSupplier;
use Database\Seeders\EagleEyeImportDefaultSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Schema;

class FscWebImportPurgeService
{
    /**
     * @return array<string, int>
     */
    public function purgeAndReseedDefaults(): array
    {
        $counts = [
            'attachments_purged' => 0,
            'action_plans_purged' => 0,
            'downtimes_purged' => 0,
            'users_purged' => 0,
            'default_users_created' => 0,
        ];

        Schema::disableForeignKeyConstraints();

        try {
            $downtimeMorph = (new MheDowntime)->getMorphClass();
            $actionPlanMorph = (new MheDowntimeActionPlan)->getMorphClass();

            $counts['attachments_purged'] = Attachment::query()
                ->whereIn('attachable_type', [$downtimeMorph, $actionPlanMorph])
                ->delete();

            $counts['action_plans_purged'] = MheDowntimeActionPlan::query()->withTrashed()->forceDelete();
            $counts['downtimes_purged'] = MheDowntime::query()->withTrashed()->forceDelete();

            EagleEyeImportLog::query()->delete();
            EagleEyeImportMap::query()->delete();
            SupplierSite::query()->delete();
            UserSupplier::query()->delete();

            $counts['users_purged'] = User::query()->withTrashed()->forceDelete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        (new UserSeeder)->run();
        (new EagleEyeImportDefaultSeeder)->run();

        $counts['default_users_created'] = User::query()->count();

        return $counts;
    }
}
