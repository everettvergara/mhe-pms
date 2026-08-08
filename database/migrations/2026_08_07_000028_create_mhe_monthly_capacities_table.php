<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhe_monthly_capacities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('yyyymm');
            $table->foreignId('mhe_inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained();
            $table->foreignId('mhe_type_id')->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit_no');
            $table->unsignedInteger('available_hours');
            $table->boolean('is_override')->default(false);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['yyyymm', 'mhe_inventory_id']);
            $table->index(['yyyymm', 'site_id', 'mhe_type_id']);
            $table->index(['yyyymm', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_monthly_capacities');
    }
};
