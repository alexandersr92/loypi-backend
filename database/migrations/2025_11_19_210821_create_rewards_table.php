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
        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id');
            $table->string('name');
            $table->enum('type', ['punch', 'streak', 'points'])->default('punch');
            $table->integer('threshold_int');
            $table->text('description')->nullable();
            $table->integer('per_customer_limit')->nullable();
            $table->integer('global_limit')->nullable();
            $table->integer('redeemed_count')->default(0);
            $table->boolean('active')->default(true);
            $table->json('reward_json')->nullable();
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index(['campaign_id', 'active']);
            $table->index('threshold_int');
            // Nota: El constraint único para type='punch' se valida en código
            // ya que MySQL no soporta unique condicionales directamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
