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
        Schema::create('customer_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('custom_field_id')->constrained('custom_fields')->onDelete('cascade');
            $table->string('string_value', 500)->nullable();
            $table->decimal('number_value', 15, 2)->nullable();
            $table->date('date_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();
            
            $table->unique(['business_id', 'customer_id', 'custom_field_id'], 'cfv_business_customer_field_unique');
            $table->index(['business_id', 'string_value']);
            $table->index(['business_id', 'number_value']);
            $table->index(['business_id', 'date_value']);
            $table->index(['business_id', 'boolean_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_field_values');
    }
};
