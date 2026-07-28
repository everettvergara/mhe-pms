<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'supplier_id']);
        });

        DB::table('users')
            ->whereNotNull('supplier_id')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('user_suppliers')->insertOrIgnore([
                    'user_id' => $user->id,
                    'supplier_id' => $user->supplier_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_suppliers');
    }
};
