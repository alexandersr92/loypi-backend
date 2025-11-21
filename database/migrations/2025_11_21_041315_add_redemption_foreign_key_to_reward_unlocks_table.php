<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('reward_unlocks') || !Schema::hasTable('redemptions')) {
            return;
        }

        // Intentar agregar la foreign key
        try {
            Schema::table('reward_unlocks', function (Blueprint $table) {
                $table->foreign('redemption_id')
                    ->references('id')
                    ->on('redemptions')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // La foreign key puede ya existir
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_unlocks', function (Blueprint $table) {
            $table->dropForeign(['redemption_id']);
        });
    }
};
