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
            // Statut de l'extraction de dialogues
            $table->string('dialogue_extraction_status')->nullable()->after('analysis_message');
            // Valeurs possibles: null (jamais lancé), 'pending', 'processing', 'completed', 'failed', 'cancelled'

            // Progression de l'extraction (0-100)
            $table->integer('dialogue_extraction_progress')->default(0)->after('dialogue_extraction_status');

            // Message de progression/erreur
            $table->text('dialogue_extraction_message')->nullable()->after('dialogue_extraction_progress');

            // Index pour améliorer les requêtes sur le statut
            $table->index('dialogue_extraction_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['dialogue_extraction_status']);
            $table->dropColumn([
                'dialogue_extraction_status',
                'dialogue_extraction_progress',
                'dialogue_extraction_message'
            ]);
        });
    }
};
