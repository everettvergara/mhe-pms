<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mhe_downtime_action_plans', 'action_plan_no')) {
            Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
                $table->string('action_plan_no')->nullable()->after('id');
                $table->string('title')->nullable()->after('action_plan_no');
                $table->text('description')->nullable()->after('title');
                $table->date('timeline_from')->nullable()->after('description');
                $table->date('timeline_to')->nullable()->after('timeline_from');
                $table->foreignId('confirmed_by')->nullable()->after('date_implemented')->constrained('users')->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
                $table->foreignId('rejected_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                $table->text('rejection_remarks')->nullable()->after('rejected_at');
            });
        }

        if (Schema::hasColumn('mhe_downtime_action_plans', 'action_plan')) {
            DB::table('mhe_downtime_action_plans')->orderBy('id')->each(function (object $row): void {
                $description = $row->action_plan ?? '';
                $title = mb_substr(trim($description), 0, 255);
                if ($title === '') {
                    $title = 'Imported Action Item';
                }

                $timelineFrom = $row->action_plan_date ?? $row->date_implemented ?? now()->toDateString();
                $timelineTo = $row->action_plan_date ?? $row->date_implemented ?? $timelineFrom;

                $status = $row->status === 'Implemented' ? 'Confirmed' : $row->status;

                DB::table('mhe_downtime_action_plans')->where('id', $row->id)->update([
                    'action_plan_no' => $row->action_plan_no ?: 'DT-AP-'.$row->id,
                    'title' => $row->title ?: $title,
                    'description' => $row->description ?: $description,
                    'timeline_from' => $row->timeline_from ?: $timelineFrom,
                    'timeline_to' => $row->timeline_to ?: $timelineTo,
                    'status' => $status,
                ]);
            });

            Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
                $table->dropColumn('action_plan');
            });
        }

        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            if (! $this->indexExists('mhe_downtime_action_plans', 'mhe_downtime_action_plans_action_plan_no_unique')) {
                $table->unique('action_plan_no');
            }

            if (! $this->indexExists('mhe_downtime_action_plans', 'mhe_downtime_action_plans_status_index')) {
                $table->index('status');
            }
        });

        if (! Schema::hasTable('mhe_downtime_action_plan_comments')) {
            Schema::create('mhe_downtime_action_plan_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mhe_downtime_action_plan_id');
                $table->text('comment');
                $table->string('progress_status');
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('created_at');

                $table->foreign('mhe_downtime_action_plan_id', 'dt_ap_comments_ap_fk')
                    ->references('id')
                    ->on('mhe_downtime_action_plans')
                    ->cascadeOnDelete();
                $table->index('mhe_downtime_action_plan_id', 'dt_ap_comments_ap_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_downtime_action_plan_comments');

        if (! Schema::hasColumn('mhe_downtime_action_plans', 'action_plan')) {
            Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
                $table->text('action_plan')->nullable()->after('action_plan_no');
            });
        }

        DB::table('mhe_downtime_action_plans')->orderBy('id')->each(function (object $row): void {
            DB::table('mhe_downtime_action_plans')->where('id', $row->id)->update([
                'action_plan' => $row->description ?? '',
                'status' => $row->status === 'Confirmed' && $row->date_implemented ? 'Implemented' : $row->status,
            ]);
        });

        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            if ($this->indexExists('mhe_downtime_action_plans', 'mhe_downtime_action_plans_action_plan_no_unique')) {
                $table->dropUnique(['action_plan_no']);
            }

            if ($this->indexExists('mhe_downtime_action_plans', 'mhe_downtime_action_plans_status_index')) {
                $table->dropIndex(['status']);
            }

            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn([
                'action_plan_no',
                'title',
                'description',
                'timeline_from',
                'timeline_to',
                'confirmed_at',
                'rejected_at',
                'rejection_remarks',
            ]);
        });
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName],
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
