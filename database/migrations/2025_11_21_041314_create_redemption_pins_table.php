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
        // No hacer drop si la tabla ya existe, solo crear si no existe
        if (Schema::hasTable('redemption_pins')) {
            return;
        }
        
        Schema::create('redemption_pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('redemption_id');
            $table->string('pin', 4);
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('redemption_id')->references('id')->on('redemptions')->onDelete('cascade');
            $table->unique(['redemption_id']); // Un PIN por redemption
            $table->index(['redemption_id']);
            $table->index(['pin', 'expires_at']); // Para búsquedas rápidas
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemption_pins');
    }
};
