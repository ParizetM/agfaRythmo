<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DetectSceneChanges;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SceneAnalysisController extends Controller
{
    /**
     * Lancer l'analyse automatique de détection de plans
     */
    public function startAnalysis(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (modification requise)
        if (!$project->canModify($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Valider les paramètres d'analyse
        $validated = $request->validate([
            'threshold' => 'nullable|numeric|min:0.1|max:1.0',
            'fps' => 'nullable|numeric|min:1|max:30',
        ]);

        // Vérifier qu'il n'y a pas déjà de scene changes
        if ($project->sceneChanges()->count() > 0) {
            return response()->json([
                'message' => 'Ce projet contient déjà des changements de plan. Supprimez-les avant de lancer une nouvelle analyse.'
            ], 422);
        }

        // Vérifier qu'il y a bien une vidéo
        if (!$project->video_path) {
            return response()->json(['message' => 'Aucune vidéo associée à ce projet'], 422);
        }

        // Vérifier qu'il n'y a pas déjà une analyse en cours
        if (in_array($project->analysis_status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Une analyse est déjà en cours'], 422);
        }

        // Paramètres par défaut
        $threshold = $validated['threshold'] ?? 0.4;
        $fps = $validated['fps'] ?? 2;

        // Réinitialiser le statut et les messages avant de lancer l'analyse
        $project->update([
            'analysis_status' => 'pending',
            'analysis_progress' => 0,
            'analysis_message' => 'Analyse en attente de démarrage...'
        ]);

        // Dispatcher le job en arrière-plan avec les paramètres
        DetectSceneChanges::dispatch($project, $threshold, $fps);

        return response()->json([
            'message' => 'Analyse lancée avec succès',
            'analysis_status' => 'pending'
        ]);
    }

    /**
     * Récupérer le statut de l'analyse
     */
    public function getStatus(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (accès en lecture)
        if (!$project->hasAccess($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'analysis_status' => $project->analysis_status,
            'analysis_progress' => $project->analysis_progress ?? 0,
            'analysis_message' => $project->analysis_message ?? '',
            'scene_changes_count' => $project->sceneChanges()->count()
        ]);
    }

    /**
     * Annuler l'analyse en cours
     */
    public function cancelAnalysis(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions (modification requise)
        if (!$project->canModify($request->user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier qu'il y a bien une analyse en cours
        if (!in_array($project->analysis_status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Aucune analyse en cours'], 422);
        }

        // Mettre le statut à "cancelled" (le job vérifiera ce statut)
        $project->update([
            'analysis_status' => 'cancelled',
            'analysis_progress' => 0,
            'analysis_message' => 'Analyse annulée par l\'utilisateur'
        ]);

        return response()->json([
            'message' => 'Analyse annulée avec succès',
            'analysis_status' => 'cancelled'
        ]);
    }
}

