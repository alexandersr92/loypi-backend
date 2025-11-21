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
        if (Schema::hasTable('redemptions')) {
            return;
        }
        
        Schema::create('redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reward_unlock_id');
            $table->uuid('customer_campaign_id');
            $table->uuid('reward_id');
            $table->uuid('staff_id')->nullable(); // Se asigna cuando el staff confirma
            $table->string('pin_code', 4);
            $table->timestamp('confirmed_at')->nullable(); // Se confirma cuando el staff canjea
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('reward_unlock_id')->references('id')->on('reward_unlocks')->onDelete('cascade');
            $table->foreign('customer_campaign_id')->references('id')->on('customer_campaigns')->onDelete('cascade');
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->index(['customer_campaign_id']);
            $table->index(['staff_id']);
            $table->index(['confirmed_at']);
            $table->index(['reward_unlock_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
