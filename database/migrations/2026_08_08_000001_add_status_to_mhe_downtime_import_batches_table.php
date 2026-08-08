<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtime_import_batches', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('dry_run');
            $table->string('phase')->nullable()->after('status');
            $table->unsignedTinyInteger('progress_percent')->default(0)->after('phase');
            $table->unsignedInteger('processed_count')->default(0)->after('progress_percent');
            $table->unsignedInteger('total_count')->default(0)->after('processed_count');
            $table->string('status_message')->nullable()->after('total_count');
            $table->json('result')->nullable()->after('status_message');
            $table->text('error_message')->nullable()->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('mhe_downtime_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'phase',
                'progress_percent',
                'processed_count',
                'total_count',
                'status_message',
                'result',
                'error_message',
            ]);
        });
    }
};
