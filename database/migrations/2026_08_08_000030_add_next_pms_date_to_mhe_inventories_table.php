<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_inventories', function (Blueprint $table) {
            $table->date('next_pms_date')->nullable()->after('remarks');
            $table->foreignId('last_pms_header_id')->nullable()->after('next_pms_date')->constrained('pms_headers')->nullOnDelete();
            $table->index('next_pms_date');
        });

        $this->backfillFromSubmittedPms();
    }

    public function down(): void
    {
        Schema::table('mhe_inventories', function (Blueprint $table) {
            $table->dropIndex(['next_pms_date']);
            $table->dropConstrainedForeignId('last_pms_header_id');
            $table->dropColumn('next_pms_date');
        });
    }

    protected function backfillFromSubmittedPms(): void
    {
        $submittedStatuses = ['With Findings', 'No Findings'];

        $latestPms = DB::table('pms_headers')
            ->select([
                'id',
                'site_id',
                'unit_number',
                'next_schedule_date',
                'submitted_at',
            ])
            ->whereIn('status', $submittedStatuses)
            ->whereNotNull('next_schedule_date')
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy(fn ($row) => $row->site_id.'|'.strtolower($row->unit_number));

        foreach ($latestPms as $rows) {
            $pms = $rows->first();

            DB::table('mhe_inventories')
                ->where('site_id', $pms->site_id)
                ->whereRaw('LOWER(unit_no) = ?', [strtolower($pms->unit_number)])
                ->update([
                    'next_pms_date' => $pms->next_schedule_date,
                    'last_pms_header_id' => $pms->id,
                ]);
        }
    }
};
