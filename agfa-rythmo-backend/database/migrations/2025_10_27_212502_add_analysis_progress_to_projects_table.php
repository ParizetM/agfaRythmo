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
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('analysis_progress')->default(0)->after('analysis_status');
            // Valeur de 0 à 100 pour le pourcentage
            $table->text('analysis_message')->nullable()->after('analysis_progress');
            // Message de progression optionnel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['analysis_progress', 'analysis_message']);
        });
    }
};
