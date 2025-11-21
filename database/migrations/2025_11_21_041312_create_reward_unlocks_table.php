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
        if (Schema::hasTable('reward_unlocks')) {
            return;
        }
        
        Schema::create('reward_unlocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_campaign_id');
            $table->uuid('reward_id');
            $table->uuid('campaign_reward_id');
            $table->timestamp('unlocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->uuid('redemption_id')->nullable();
            $table->enum('status', ['unlocked', 'redeemed', 'expired'])->default('unlocked');
            $table->timestamps();

            $table->foreign('customer_campaign_id')->references('id')->on('customer_campaigns')->onDelete('cascade');
            $table->foreign('reward_id')->references('id')->on('rewards')->onDelete('cascade');
            $table->foreign('campaign_reward_id')->references('id')->on('campaign_reward')->onDelete('cascade');
            // redemption_id se agregará después de crear la tabla redemptions
            $table->index(['customer_campaign_id', 'status']);
            $table->index(['expires_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_unlocks');
    }
};
