<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pms_headers', function (Blueprint $table) {
            $table->date('next_schedule_date')->nullable()->after('date_to');
            $table->index('next_schedule_date');
        });
    }

    public function down(): void
    {
        Schema::table('pms_headers', function (Blueprint $table) {
            $table->dropIndex(['next_schedule_date']);
            $table->dropColumn('next_schedule_date');
        });
    }
};
