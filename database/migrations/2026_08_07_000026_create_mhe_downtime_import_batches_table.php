<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhe_downtime_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique();
            $table->string('source');
            $table->string('source_summary')->nullable();
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('downtimes')->default(0);
            $table->unsignedInteger('action_plans')->default(0);
            $table->unsignedInteger('downtime_attachments')->default(0);
            $table->unsignedInteger('action_plan_attachments')->default(0);
            $table->unsignedInteger('warnings')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_downtime_import_batches');
    }
};
