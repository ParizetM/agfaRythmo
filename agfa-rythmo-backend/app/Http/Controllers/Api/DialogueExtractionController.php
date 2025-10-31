<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractDialogues;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialogueExtractionController extends Controller
{
    /**
     * Lancer l'extraction automatique de dialogues
     */
    public function startExtraction(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (modification requise)
        if (!$project->canModify($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Valider les paramètres d'extraction
        $validated = $request->validate([
            'language' => 'nullable|string|max:10',
            'max_speakers' => 'nullable|integer|min:2|max:20',
            'whisper_model' => 'nullable|string|in:tiny,base,small,medium,large',
        ]);

        // ⚠️ INVERSE de scene detection : vérifier qu'il N'Y A PAS de timecodes
        if ($project->timecodes()->count() > 0) {
            return response()->json([
                'message' => 'Ce projet contient déjà des timecodes. Supprimez-les avant de lancer l\'extraction automatique.'
            ], 422);
        }

        // Vérifier qu'il y a bien une vidéo
        if (!$project->video_path) {
            return response()->json(['message' => 'Aucune vidéo associée à ce projet'], 422);
        }

        // Vérifier qu'il n'y a pas déjà une extraction en cours
        if (in_array($project->dialogue_extraction_status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Une extraction est déjà en cours'], 422);
        }

        // Paramètres par défaut depuis config/ai.php
        $language = $validated['language'] ?? 'auto';
        $maxSpeakers = $validated['max_speakers'] ?? config('ai.max_speakers', 10);
        $whisperModel = $validated['whisper_model'] ?? config('ai.whisper_model', 'tiny');

        // Réinitialiser le statut et les messages avant de lancer l'extraction
        $project->update([
            'dialogue_extraction_status' => 'pending',
            'dialogue_extraction_progress' => 0,
            'dialogue_extraction_message' => 'Extraction en attente de démarrage...'
        ]);

        // Dispatcher le job en arrière-plan avec les paramètres
        ExtractDialogues::dispatch($project, $language, $maxSpeakers, $whisperModel);

        return response()->json([
            'message' => 'Extraction lancée avec succès',
            'dialogue_extraction_status' => 'pending',
            'parameters' => [
                'language' => $language,
                'max_speakers' => $maxSpeakers,
                'whisper_model' => $whisperModel
            ]
        ]);
    }

    /**
     * Récupérer le statut de l'extraction
     */
    public function getStatus(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (accès en lecture)
        if (!$project->hasAccess($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $settings = $project->project_settings ?? [];
        $sourceLanguage = $settings['last_extraction_language'] ?? null;

        return response()->json([
            'dialogue_extraction_status' => $project->dialogue_extraction_status,
            'dialogue_extraction_progress' => $project->dialogue_extraction_progress ?? 0,
            'dialogue_extraction_message' => $project->dialogue_extraction_message ?? '',
            'timecodes_count' => $project->timecodes()->count(),
            'characters_count' => $project->characters()->count(),
            'source_language' => $sourceLanguage
        ]);
    }

    /**
     * Annuler l'extraction en cours
     */
    public function cancelExtraction(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (modification requise)
        if (!$project->canModify($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier qu'il y a bien une extraction en cours
        if (!in_array($project->dialogue_extraction_status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Aucune extraction en cours'], 422);
        }

        // Mettre le statut à "cancelled" (le job vérifiera ce statut)
        $project->update([
            'dialogue_extraction_status' => 'cancelled',
            'dialogue_extraction_progress' => 0,
            'dialogue_extraction_message' => 'Extraction annulée par l\'utilisateur'
        ]);

        return response()->json([
            'message' => 'Extraction annulée avec succès',
            'dialogue_extraction_status' => 'cancelled'
        ]);
    }
}
