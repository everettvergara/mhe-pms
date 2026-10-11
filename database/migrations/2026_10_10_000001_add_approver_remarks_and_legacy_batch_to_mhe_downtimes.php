<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtimes', function (Blueprint $table) {
            $table->text('approver_remarks')->nullable()->after('description');
            $table->uuid('legacy_import_batch_id')->nullable()->after('legacy_eagle_eye_id');
            $table->index('legacy_import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('mhe_downtimes', function (Blueprint $table) {
            $table->dropIndex(['legacy_import_batch_id']);
            $table->dropColumn(['approver_remarks', 'legacy_import_batch_id']);
        });
    }
};
