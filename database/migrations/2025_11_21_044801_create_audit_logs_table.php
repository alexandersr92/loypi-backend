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
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action'); // create, update, delete, login, logout, etc.
            $table->string('actor_type'); // App\Models\User, App\Models\Staff, App\Models\Customer
            $table->uuid('actor_id');
            $table->string('auditable_type')->nullable(); // Modelo afectado (App\Models\Campaign, etc.)
            $table->uuid('auditable_id')->nullable(); // ID del modelo afectado
            $table->string('description')->nullable(); // Descripción legible
            $table->json('old_values')->nullable(); // Valores anteriores
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->json('meta')->nullable(); // Metadatos adicionales (IP, user agent, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['actor_type', 'actor_id']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
            $table->index('created_at');
            $table->index(['actor_type', 'actor_id', 'created_at']);
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
