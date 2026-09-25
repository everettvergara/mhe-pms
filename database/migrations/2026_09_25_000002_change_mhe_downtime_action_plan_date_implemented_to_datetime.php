<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->dateTime('date_implemented')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->date('date_implemented')->nullable()->change();
        });
    }
};
