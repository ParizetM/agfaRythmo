<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TranslateDialogues;
use App\Models\Project;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    /**
     * Démarrer la traduction des dialogues
     */
    public function startTranslation(Request $request, Project $project)
    {
        // Vérifier que l'utilisateur a accès au projet
        if (!$project->canModify(auth()->user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Valider les paramètres
        $validated = $request->validate([
            'target_language' => 'required|string|min:2|max:5',
            'source_language' => 'nullable|string|min:2|max:5',
            'use_character_context' => 'nullable|boolean',
        ]);

        // Vérifier qu'il y a des timecodes à traduire
        $timecodesCount = $project->timecodes()->count();
        if ($timecodesCount === 0) {
            return response()->json([
                'message' => 'Aucun timecode à traduire. Veuillez d\'abord extraire les dialogues.'
            ], 400);
        }

        // Vérifier qu'une traduction n'est pas déjà en cours
        if (in_array($project->translation_status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Une traduction est déjà en cours pour ce projet'
            ], 409);
        }

        try {
            // Initialiser le statut
            $project->update([
                'translation_status' => 'pending',
                'translation_progress' => 0,
                'translation_message' => 'En attente de traitement...',
                'source_language' => $validated['source_language'] ?? 'auto',
                'target_language' => $validated['target_language'],
            ]);

            // Dispatcher le job de traduction
            TranslateDialogues::dispatch(
                $project,
                $validated['target_language'],
                $validated['source_language'] ?? null,
                $validated['use_character_context'] ?? config('ai.translation.use_character_context', true)
            );

            Log::info("Traduction lancée pour le projet {$project->id}", [
                'target_language' => $validated['target_language'],
                'source_language' => $validated['source_language'] ?? 'auto',
                'timecodes_count' => $timecodesCount,
            ]);

            return response()->json([
                'message' => 'Traduction démarrée',
                'translation_status' => 'pending',
                'timecodes_count' => $timecodesCount,
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors du lancement de la traduction: " . $e->getMessage());

            $project->update([
                'translation_status' => 'failed',
                'translation_message' => 'Erreur: ' . $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du lancement de la traduction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut de la traduction
     */
    public function getStatus(Project $project)
    {
        // Vérifier que l'utilisateur a accès au projet
        if (!$project->hasAccess(auth()->user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return response()->json([
            'translation_status' => $project->translation_status,
            'translation_progress' => $project->translation_progress ?? 0,
            'translation_message' => $project->translation_message,
            'source_language' => $project->source_language,
            'target_language' => $project->target_language,
            'timecodes_count' => $project->timecodes()->count(),
        ]);
    }

    /**
     * Annuler la traduction en cours
     */
    public function cancelTranslation(Project $project)
    {
        // Vérifier que l'utilisateur a accès au projet
        if (!$project->canModify(auth()->user())) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Vérifier qu'une traduction est en cours
        if (!in_array($project->translation_status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Aucune traduction en cours pour ce projet'
            ], 400);
        }

        // Marquer comme annulé (le job vérifiera ce statut)
        $project->update([
            'translation_status' => 'cancelled',
            'translation_message' => 'Annulation en cours...',
        ]);

        Log::info("Traduction annulée pour le projet {$project->id}");

        return response()->json([
            'message' => 'Traduction annulée'
        ]);
    }

    /**
     * Obtenir les langues supportées
     */
    public function getSupportedLanguages()
    {
        try {
            $translationService = new TranslationService();
            $languages = $translationService->getSupportedLanguages();

            return response()->json([
                'languages' => $languages,
                'provider' => config('ai.translation.provider', 'libretranslate'),
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des langues supportées: " . $e->getMessage());

            // Retourner langues par défaut en cas d'erreur
            return response()->json([
                'languages' => [
                    ['code' => 'en', 'name' => 'English'],
                    ['code' => 'fr', 'name' => 'Français'],
                    ['code' => 'es', 'name' => 'Español'],
                    ['code' => 'de', 'name' => 'Deutsch'],
                    ['code' => 'it', 'name' => 'Italiano'],
                    ['code' => 'pt', 'name' => 'Português'],
                    ['code' => 'ru', 'name' => 'Русский'],
                    ['code' => 'zh', 'name' => '中文'],
                    ['code' => 'ja', 'name' => '日本語'],
                    ['code' => 'ko', 'name' => '한국어'],
                ],
                'provider' => config('ai.translation.provider', 'libretranslate'),
                'error' => 'Impossible de récupérer les langues depuis le provider, liste par défaut utilisée'
            ]);
        }
    }
}
