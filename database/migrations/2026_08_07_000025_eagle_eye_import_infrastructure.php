<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhe_downtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_eagle_eye_id')->nullable()->unique()->after('id');
            $table->foreignId('mhe_inventory_id')->nullable()->after('mhe_category_id')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('mhe_inventory_id')->constrained()->nullOnDelete();
        });

        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_eagle_eye_id')->nullable()->unique()->after('id');
            $table->string('responsible_person')->nullable()->after('action_plan');
            $table->date('action_plan_date')->nullable()->after('responsible_person');
        });

        Schema::create('eagle_eye_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36);
            $table->string('level', 16);
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('batch_id');
        });

        Schema::create('eagle_eye_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('local_id');
            $table->string('legacy_code')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eagle_eye_import_maps');
        Schema::dropIfExists('eagle_eye_import_logs');

        Schema::table('mhe_downtime_action_plans', function (Blueprint $table) {
            $table->dropUnique(['legacy_eagle_eye_id']);
            $table->dropColumn(['legacy_eagle_eye_id', 'responsible_person', 'action_plan_date']);
        });

        Schema::table('mhe_downtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('mhe_inventory_id');
            $table->dropUnique(['legacy_eagle_eye_id']);
            $table->dropColumn('legacy_eagle_eye_id');
        });
    }
};
