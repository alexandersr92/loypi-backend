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
        Schema::table('rewards', function (Blueprint $table) {
            // Agregar business_id para templates
            $table->uuid('business_id')->nullable()->after('id');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
        });
        
        // Hacer campaign_id nullable (requiere hacerlo en una segunda operación)
        Schema::table('rewards', function (Blueprint $table) {
            $table->uuid('campaign_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            // Restaurar campaign_id como requerido
            $table->uuid('campaign_id')->nullable(false)->change();
        });
        
        Schema::table('rewards', function (Blueprint $table) {
            // Eliminar la foreign key y el índice de business_id
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};
