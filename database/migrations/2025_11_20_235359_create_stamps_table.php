<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_campaign_id');
            $table->uuid('staff_id');
            $table->enum('type', ['stamp', 'streak']);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('customer_campaign_id')->references('id')->on('customer_campaigns')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->index(['customer_campaign_id', 'created_at']);
            $table->index(['staff_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
