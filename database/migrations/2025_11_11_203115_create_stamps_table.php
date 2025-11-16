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
        Schema::create('stamps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_campaign_id')->constrained('customer_campaigns')->onDelete('cascade');
            $table->foreignUuid('staff_id')->constrained('staff')->onDelete('cascade');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['customer_campaign_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
