<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtime_import_batches', function (Blueprint $table) {
            $table->unsignedInteger('users')->default(0)->after('dry_run');
            $table->unsignedInteger('users_purged')->default(0)->after('users');
            $table->unsignedInteger('downtimes_purged')->default(0)->after('users_purged');
            $table->unsignedInteger('action_plans_purged')->default(0)->after('downtimes_purged');
        });
    }

    public function down(): void
    {
        Schema::table('mhe_downtime_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'users',
                'users_purged',
                'downtimes_purged',
                'action_plans_purged',
            ]);
        });
    }
};
