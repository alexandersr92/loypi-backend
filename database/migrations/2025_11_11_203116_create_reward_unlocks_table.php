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
        Schema::create('reward_unlocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reward_id')->constrained('rewards')->onDelete('cascade');
            $table->foreignUuid('customer_campaign_id')->constrained('customer_campaigns')->onDelete('cascade');
            $table->timestamp('unlocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignUuid('redemption_id')->nullable()->constrained('redemptions')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['reward_id', 'customer_campaign_id']);
            $table->index(['customer_campaign_id']);
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
