<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('key');
            $table->string('label');
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'number', 'date', 'boolean', 'select'])->default('text');
            $table->boolean('required')->default(false);
            $table->json('extra')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'key']);
            $table->index(['business_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
