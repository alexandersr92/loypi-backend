<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_reward', function (Blueprint $table) {
            $table->integer('expires_after_days')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_reward', function (Blueprint $table) {
            $table->dropColumn('expires_after_days');
        });
    }
};
