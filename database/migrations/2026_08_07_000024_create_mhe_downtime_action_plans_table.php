<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mhe_downtime_id')->constrained()->cascadeOnDelete();
            $table->text('action_plan');
            $table->string('status')->default('Pending');
            $table->date('date_implemented')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_downtime_action_plans');
    }
};
