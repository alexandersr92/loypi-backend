<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_field_values');
        
        Schema::create('customer_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('campaign_id');
            $table->uuid('customer_id');
            $table->uuid('custom_field_id');
            $table->text('string_value')->nullable();
            $table->decimal('number_value', 15, 2)->nullable();
            $table->date('date_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
            // customer_id foreign key se agregará después de crear customers table
            $table->unique(['customer_id', 'campaign_id', 'custom_field_id'], 'cfv_customer_campaign_field_unique');
            $table->index(['business_id', 'customer_id']);
            $table->index(['business_id', 'campaign_id']);
            // No se pueden crear índices en campos TEXT, solo en VARCHAR
            // $table->index(['business_id', 'string_value']);
            $table->index(['business_id', 'number_value']);
            $table->index(['business_id', 'date_value']);
            $table->index(['business_id', 'boolean_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_field_values');
    }
};
