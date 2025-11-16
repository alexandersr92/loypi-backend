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
        Schema::create('redemption_pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('redemption_id')->constrained('redemptions')->onDelete('cascade');
            $table->string('pin_hash');
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->timestamps();
            
            $table->index('redemption_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemption_pins');
    }
};
