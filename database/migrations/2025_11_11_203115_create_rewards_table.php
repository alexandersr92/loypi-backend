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
            $table->foreignUuid('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['punch', 'streak', 'points'])->comment('Tipo de condición');
            $table->integer('threshold_int')->comment('Ej: sellos requeridos o días de racha');
            $table->text('description')->nullable();
            $table->integer('per_customer_limit')->nullable()->comment('NULL = ilimitado por cliente');
            $table->integer('global_limit')->nullable()->comment('NULL = sin límite adicional');
            $table->integer('redeemed_count')->default(0)->comment('Canjes realizados de este reward');
            $table->boolean('active')->default(true);
            $table->json('reward_json')->nullable()->comment('Detalles del premio');
            $table->timestamps();
            
            $table->index(['campaign_id', 'active']);
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
