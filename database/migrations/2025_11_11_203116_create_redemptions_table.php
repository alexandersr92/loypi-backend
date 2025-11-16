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
        Schema::create('redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_campaign_id')->constrained('customer_campaigns')->onDelete('cascade');
            $table->foreignUuid('reward_id')->constrained('rewards')->onDelete('cascade');
            $table->foreignUuid('staff_id')->constrained('staff')->onDelete('cascade');
            $table->timestamp('confirmed_at');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['customer_campaign_id']);
            $table->index(['reward_id']);
            $table->index(['staff_id']);
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
