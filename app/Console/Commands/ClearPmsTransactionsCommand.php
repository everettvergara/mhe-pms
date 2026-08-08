<?php

namespace App\Console\Commands;

use App\Models\ActionPlan;
use App\Models\Attachment;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClearPmsTransactionsCommand extends Command
{
    protected $signature = 'pms:clear-transactions
                            {--force : Run without confirmation}';

    protected $description = 'Remove PMS transaction records, action plans/items, attachments, and related sequences/logs. Masters and users are kept.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will permanently delete all PMS and action-plan transaction data. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $counts = DB::transaction(function (): array {
            $attachmentQuery = Attachment::query()->where(function ($query): void {
                $query->where('attachable_type', PmsHeader::class)
                    ->orWhere('attachable_type', PmsDetail::class)
                    ->orWhere('attachable_type', (new PmsHeader)->getMorphClass())
                    ->orWhere('attachable_type', (new PmsDetail)->getMorphClass());
            });

            $attachmentPaths = $attachmentQuery->pluck('file_path')->all();
            $attachments = $attachmentQuery->count();
            $attachmentQuery->delete();

            foreach ($attachmentPaths as $path) {
                if ($path && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }

            $actionPlanComments = 0;
            if (Schema::hasTable('action_plan_comments')) {
                $actionPlanComments = DB::table('action_plan_comments')->count();
                DB::table('action_plan_comments')->delete();
            }

            $actionPlans = ActionPlan::query()->count();
            ActionPlan::query()->delete();

            $pmsDetails = PmsDetail::query()->count();
            PmsDetail::query()->delete();

            $pmsHeaders = PmsHeader::query()->count();
            PmsHeader::query()->delete();

            $sequences = 0;
            if (Schema::hasTable('number_sequences')) {
                $sequences = DB::table('number_sequences')->whereIn('type', ['pms', 'action_plan'])->count();
                DB::table('number_sequences')->whereIn('type', ['pms', 'action_plan'])->delete();
            }

            $activityLogs = 0;
            if (Schema::hasTable('activity_logs')) {
                $activityLogs = DB::table('activity_logs')
                    ->whereIn('module', ['pms', 'action-plans', 'action-plan-confirmations'])
                    ->count();
                DB::table('activity_logs')
                    ->whereIn('module', ['pms', 'action-plans', 'action-plan-confirmations'])
                    ->delete();
            }

            return compact(
                'pmsHeaders',
                'pmsDetails',
                'actionPlans',
                'actionPlanComments',
                'attachments',
                'sequences',
                'activityLogs',
            );
        });

        $this->table(
            ['Table / item', 'Deleted'],
            [
                ['pms_headers', $counts['pmsHeaders']],
                ['pms_details', $counts['pmsDetails']],
                ['action_plans', $counts['actionPlans']],
                ['action_plan_comments', $counts['actionPlanComments']],
                ['attachments', $counts['attachments']],
                ['number_sequences (pms/action_plan)', $counts['sequences']],
                ['activity_logs (pms modules)', $counts['activityLogs']],
            ],
        );

        $this->info('PMS transactions and action items cleared. Masters and users were left intact.');

        return self::SUCCESS;
    }
}
