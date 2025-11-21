<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('custom_field_id');
            $table->string('operator');
            $table->text('value_string')->nullable();
            $table->decimal('value_number', 15, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
            $table->index(['custom_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_validations');
    }
};
