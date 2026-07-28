<?php

use App\Enums\RecordStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('id')->constrained();
        });

        $districtId = DB::table('districts')->where('district_code', 'PH')->value('id');

        if (! $districtId) {
            $districtId = DB::table('districts')->insertGetId([
                'district_code' => 'PH',
                'district_name' => 'Philippines',
                'description' => 'Default district for all warehouses',
                'status' => RecordStatus::Active->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('sites')->whereNull('district_id')->update(['district_id' => $districtId]);

        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
        });
    }
};
