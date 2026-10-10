<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->boolean('unit_safe_guaranteed')->default(false)->after('status');
            $table->foreignId('unit_safe_guaranteed_by')->nullable()->after('unit_safe_guaranteed')->constrained('users')->nullOnDelete();
            $table->timestamp('unit_safe_guaranteed_at')->nullable()->after('unit_safe_guaranteed_by');
        });
    }

    public function down(): void
    {
        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_safe_guaranteed_by');
            $table->dropColumn(['unit_safe_guaranteed', 'unit_safe_guaranteed_at']);
        });
    }
};
