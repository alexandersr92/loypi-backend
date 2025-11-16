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
        Schema::create('customer_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->integer('stamps')->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['customer_id', 'campaign_id']);
            $table->index(['customer_id']);
            $table->index(['campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_campaigns');
    }
};
