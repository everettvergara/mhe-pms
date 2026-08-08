<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhe_downtimes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('site_id')->constrained();
            $table->foreignId('mhe_type_id')->constrained();
            $table->foreignId('mhe_category_id')->constrained();
            $table->string('ref_unit_no');
            $table->dateTime('date_of_incident');
            $table->dateTime('uptime')->nullable();
            $table->decimal('hours_down', 10, 2)->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('description')->nullable();
            $table->boolean('w_spare_unit')->default(false);
            $table->string('status')->default('Draft');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_downtimes');
    }
};
