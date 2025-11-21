<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->uuid('campaign_id');
            $table->integer('stamps')->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->unique(['customer_id', 'campaign_id']);
            $table->index(['customer_id']);
            $table->index(['campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_campaigns');
    }
};
