<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('short_code', 6)->unique();
            $table->string('phone');
            $table->string('name');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'phone']);
            $table->index(['business_id']);
            $table->index(['short_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
