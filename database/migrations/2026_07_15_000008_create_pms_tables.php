<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['type', 'year']);
        });

        Schema::create('pms_headers', function (Blueprint $table) {
            $table->id();
            $table->string('pms_no')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('technician_name', 150);
            $table->dateTime('date_from');
            $table->dateTime('date_to');
            $table->foreignId('mhe_type_id')->constrained()->restrictOnDelete();
            $table->string('unit_number', 100);
            $table->string('serial_number', 100);
            $table->string('status')->default('Draft');
            $table->string('action_plan_status')->default('None');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('site_id');
            $table->index('status');
            $table->index('action_plan_status');
            $table->index('date_from');
        });

        Schema::create('pms_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pms_header_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->restrictOnDelete();
            $table->string('answer')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('pms_header_id');
            $table->index('checklist_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pms_details');
        Schema::dropIfExists('pms_headers');
        Schema::dropIfExists('number_sequences');
    }
};
