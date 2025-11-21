<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Hacer que code sea nullable (porque Twilio Verify maneja el código internamente)
            $table->string('code', 6)->nullable()->change();
            // Agregar campo meta para guardar información de Twilio
            $table->json('meta')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn('meta');
            // Restaurar code a not null si es necesario
            $table->string('code', 6)->nullable(false)->change();
        });
    }
};
