<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_field_values') || !Schema::hasTable('customers')) {
            return;
        }

        // Intentar agregar la foreign key
        try {
            Schema::table('customer_field_values', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // La foreign key puede ya existir
        }
    }

    public function down(): void
    {
        Schema::table('customer_field_values', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
};
