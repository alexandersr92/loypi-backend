<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rewards')) {
            return;
        }

        Schema::table('rewards', function (Blueprint $table) {
            // Intentar eliminar foreign key si existe
            try {
                $table->dropForeign(['campaign_id']);
            } catch (\Exception $e) {
                // El foreign key puede no existir
            }
            
            // Eliminar índices si existen
            try {
                $table->dropIndex(['campaign_id', 'active']);
            } catch (\Exception $e) {
                // El índice puede no existir
            }
            
            try {
                $table->dropIndex(['threshold_int']);
            } catch (\Exception $e) {
                // El índice puede no existir
            }
            
            // Eliminar campos que ahora van a campaign_reward (solo si existen)
            $columnsToDrop = [];
            
            if (Schema::hasColumn('rewards', 'campaign_id')) {
                $columnsToDrop[] = 'campaign_id';
            }
            if (Schema::hasColumn('rewards', 'threshold_int')) {
                $columnsToDrop[] = 'threshold_int';
            }
            if (Schema::hasColumn('rewards', 'per_customer_limit')) {
                $columnsToDrop[] = 'per_customer_limit';
            }
            if (Schema::hasColumn('rewards', 'global_limit')) {
                $columnsToDrop[] = 'global_limit';
            }
            if (Schema::hasColumn('rewards', 'redeemed_count')) {
                $columnsToDrop[] = 'redeemed_count';
            }
            if (Schema::hasColumn('rewards', 'active')) {
                $columnsToDrop[] = 'active';
            }
            
            if (!empty($columnsToDrop)) {
                try {
                    $table->dropColumn($columnsToDrop);
                } catch (\Exception $e) {
                    // Las columnas pueden no existir o no poder ser eliminadas
                }
            }
            
            // Agregar image_url para la foto del premio (solo si no existe)
            if (!Schema::hasColumn('rewards', 'image_url')) {
                try {
                    $table->string('image_url')->nullable()->after('description');
                } catch (\Exception $e) {
                    // Puede fallar en algunos drivers
                    if (!Schema::hasColumn('rewards', 'image_url')) {
                        $table->string('image_url')->nullable();
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            // Restaurar campos
            $table->uuid('campaign_id')->nullable()->after('business_id');
            $table->integer('threshold_int')->after('description');
            $table->integer('per_customer_limit')->nullable()->after('threshold_int');
            $table->integer('global_limit')->nullable()->after('per_customer_limit');
            $table->integer('redeemed_count')->default(0)->after('global_limit');
            $table->boolean('active')->default(true)->after('redeemed_count');
            
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->index(['campaign_id', 'active']);
            $table->index('threshold_int');
            
            // Eliminar image_url
            $table->dropColumn('image_url');
        });
    }
};
