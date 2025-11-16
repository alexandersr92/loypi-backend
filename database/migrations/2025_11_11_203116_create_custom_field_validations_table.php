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
        Schema::create('custom_field_validations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('custom_field_id')->constrained('custom_fields')->onDelete('cascade');
            $table->string('operator')->comment('=, !=, >, >=, <, <=, in, not_in, regex, etc.');
            $table->text('value_string')->nullable();
            $table->decimal('value_number', 15, 2)->nullable();
            $table->date('value_date')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            
            $table->index('custom_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_validations');
    }
};
