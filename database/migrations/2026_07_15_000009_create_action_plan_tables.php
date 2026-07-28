<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->string('action_plan_no')->unique();
            $table->foreignId('pms_detail_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('responsible_person');
            $table->date('timeline_from');
            $table->date('timeline_to');
            $table->string('status')->default('Pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pms_detail_id');
            $table->index('status');
            $table->index('responsible_person');
        });

        Schema::create('action_plan_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->string('progress_status');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at');

            $table->index('action_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_plan_comments');
        Schema::dropIfExists('action_plans');
    }
};
