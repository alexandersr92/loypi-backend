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
        Schema::table('custom_fields', function (Blueprint $table) {
            // Change the 'type' column from ENUM to STRING to allow more flexibility (e.g. 'phone')
            // Application logic (FormRequests) will handle validation of allowed types.
            $table->string('type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            // Revert back to ENUM if possible.
            // WARNING: This will fail if there are records with type='phone' or other new types.
            $table->enum('type', ['text', 'number', 'date', 'boolean', 'select'])->default('text')->change();
        });
    }
};
