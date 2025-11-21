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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('type', ['punch', 'streak'])->default('punch');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('limit')->nullable();
            $table->integer('redeemed_count')->default(0);
            $table->json('reward_json')->nullable();
            $table->integer('required_stamps')->nullable();
            $table->boolean('active')->default(true);
            $table->string('cover_image')->nullable();
            $table->string('cover_color')->nullable();
            $table->string('logo_url')->nullable();
            $table->integer('streak_time_limit_hours')->nullable();
            $table->time('streak_reset_time')->nullable();
            $table->integer('per_customer_limit')->nullable();
            $table->integer('per_week_limit')->nullable();
            $table->integer('per_month_limit')->nullable();
            $table->integer('max_redemptions_per_day')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index(['business_id', 'active']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
