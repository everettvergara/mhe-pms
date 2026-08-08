<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhe_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('district')->nullable();
            $table->string('site')->nullable();
            $table->foreignId('site_id')->nullable()->constrained('sites');
            $table->string('provider')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('equipment_type')->nullable();
            $table->foreignId('mhe_type_id')->nullable()->constrained('mhe_types');
            $table->string('unit_no')->nullable();
            $table->string('unit_role')->nullable()->default('Primary');
            $table->string('equipment_status')->default('Active');
            $table->string('client_fsc')->nullable();
            $table->string('years_in_service')->nullable();
            $table->string('total_kl_run')->nullable();
            $table->string('total_down_hours')->nullable();
            $table->string('battery_unit_no')->nullable();
            $table->string('battery_years')->nullable();
            $table->string('battery_man_count')->nullable();
            $table->string('technicians_on_site')->nullable();
            $table->string('branch_location')->nullable();
            $table->string('total_technicians')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'unit_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhe_inventories');
    }
};
