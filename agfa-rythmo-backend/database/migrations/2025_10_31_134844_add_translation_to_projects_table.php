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
            // Statut de la traduction (null, pending, processing, completed, failed, cancelled)
            $table->string('translation_status')->nullable()->after('dialogue_extraction_message');

            // Progression de la traduction (0-100)
            $table->integer('translation_progress')->default(0)->after('translation_status');

            // Message de statut de la traduction
            $table->text('translation_message')->nullable()->after('translation_progress');

            // Langue source (auto, en, fr, zh, ja, etc.)
            $table->string('source_language')->nullable()->after('translation_message');

            // Langue cible (en, fr, zh, ja, es, de, it, pt, ru, ko, ar, hi, etc.)
            $table->string('target_language')->nullable()->after('source_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'translation_status',
                'translation_progress',
                'translation_message',
                'source_language',
                'target_language',
            ]);
        });
    }
};
