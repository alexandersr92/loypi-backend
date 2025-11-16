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
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('limit')->nullable()->comment('Límite global de canjes (NULL = ilimitado)');
            $table->integer('redeemed_count')->default(0)->comment('Cache de canjes');
            $table->json('reward_json')->nullable()->comment('Detalle libre del premio');
            $table->integer('required_stamps')->nullable()->comment('Legado para punch simple');
            $table->boolean('active')->default(true);
            $table->string('cover_image')->nullable();
            $table->string('cover_color')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'active']);
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
