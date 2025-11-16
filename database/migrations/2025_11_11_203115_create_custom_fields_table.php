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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('campaign_id')->nullable()->constrained('campaigns')->onDelete('cascade')->comment('NULL = campo de perfil de negocio');
            $table->string('key');
            $table->string('label');
            $table->string('type')->comment('text | number | date | boolean | select');
            $table->boolean('required')->default(false);
            $table->json('extra')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['business_id', 'campaign_id', 'key']);
            $table->index(['business_id', 'campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
