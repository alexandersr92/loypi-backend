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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->nullable()->constrained('businesses')->onDelete('cascade')->comment('NULL: logs del sistema global');
            $table->string('actor_type')->comment('user | staff | system | customer');
            $table->uuid('actor_id')->nullable();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
