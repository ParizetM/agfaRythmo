<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\SceneChangeController;
use App\Http\Controllers\TimecodeController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ProjectCollaboratorController;
use App\Http\Controllers\Api\ProjectInvitationController;
use App\Http\Controllers\Api\SettingsPresetController;
use App\Http\Controllers\Api\SceneAnalysisController;
use App\Http\Controllers\Api\DialogueExtractionController;
use App\Http\Controllers\Api\TranslationController;

// Routes publiques (pas d'authentification requise)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Capacités du serveur (public pour vérification avant connexion)
Route::get('/server/capabilities', function () {
    return response()->json(\App\Services\ServerCapabilities::getCapabilities());
});

// Streaming vidéo (public pour compatibility avec les balises <video>)
Route::get('/videos/{filename}', [VideoController::class, 'stream'])->where('filename', '.*');

// Routes protégées (authentification requise)
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Projets
    Route::apiResource('projects', ProjectController::class);
    Route::patch('/projects/{project}/rythmo-lines', [ProjectController::class, 'updateRythmoLinesCount']);
    Route::patch('/projects/{project}/settings', [ProjectController::class, 'updateSettings']);
    Route::get('/projects/{project}/export', [ProjectController::class, 'export']);
    Route::post('/projects/import', [ProjectController::class, 'import']);

    // Vidéos
    Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

    // Changements de plan (scene changes)
    Route::get('/projects/{project}/scene-changes', [SceneChangeController::class, 'index']);
    Route::post('/projects/{project}/scene-changes', [SceneChangeController::class, 'store']);
    Route::delete('/projects/{project}/scene-changes', [SceneChangeController::class, 'destroyAll']);
    Route::put('/scene-changes/{id}', [SceneChangeController::class, 'update']);
    Route::delete('/scene-changes/{id}', [SceneChangeController::class, 'destroy']);

    // Analyse IA des changements de plan
    Route::post('/projects/{project}/analyze-scenes', [SceneAnalysisController::class, 'startAnalysis']);
    Route::get('/projects/{project}/analysis-status', [SceneAnalysisController::class, 'getStatus']);
    Route::post('/projects/{project}/cancel-analysis', [SceneAnalysisController::class, 'cancelAnalysis']);

    // Extraction automatique de dialogues (Whisper + diarization)
    Route::post('/projects/{project}/dialogue-extraction/start', [DialogueExtractionController::class, 'startExtraction']);
    Route::get('/projects/{project}/dialogue-extraction/status', [DialogueExtractionController::class, 'getStatus']);
    Route::post('/projects/{project}/dialogue-extraction/cancel', [DialogueExtractionController::class, 'cancelExtraction']);

    // Traduction automatique de dialogues
    Route::post('/projects/{project}/translation/start', [TranslationController::class, 'startTranslation']);
    Route::get('/projects/{project}/translation/status', [TranslationController::class, 'getStatus']);
    Route::post('/projects/{project}/translation/cancel', [TranslationController::class, 'cancelTranslation']);
    Route::get('/translation/supported-languages', [TranslationController::class, 'getSupportedLanguages']);

    // Timecodes multi-lignes
    Route::get('/projects/{project}/timecodes', [TimecodeController::class, 'index']);
    Route::post('/projects/{project}/timecodes', [TimecodeController::class, 'store']);
    Route::delete('/projects/{project}/timecodes', [TimecodeController::class, 'destroyAll']);
    Route::post('/projects/{project}/timecodes/import-srt', [TimecodeController::class, 'importSrt']);
    Route::get('/projects/{project}/timecodes/{timecode}', [TimecodeController::class, 'show']);
    Route::put('/projects/{project}/timecodes/{timecode}', [TimecodeController::class, 'update']);
    Route::delete('/projects/{project}/timecodes/{timecode}', [TimecodeController::class, 'destroy']);
    Route::get('/projects/{project}/timecodes/line/{lineNumber}', [TimecodeController::class, 'getByLine']);

    // Characters
    Route::get('/projects/{project}/characters', [CharacterController::class, 'index']);
    Route::post('/characters', [CharacterController::class, 'store']);
    Route::put('/characters/{character}', [CharacterController::class, 'update']);
    Route::delete('/characters/{character}', [CharacterController::class, 'destroy']);
    Route::post('/characters/clone', [CharacterController::class, 'clone']);
    Route::post('/characters/merge', [CharacterController::class, 'merge']);
    Route::get('/characters/for-cloning', [CharacterController::class, 'getForCloning']);

    // Settings Presets (max 5 par utilisateur)
    Route::get('/settings-presets', [SettingsPresetController::class, 'index']);
    Route::post('/settings-presets', [SettingsPresetController::class, 'store']);
    Route::put('/settings-presets/{preset}', [SettingsPresetController::class, 'update']);
    Route::delete('/settings-presets/{preset}', [SettingsPresetController::class, 'destroy']);

    // Collaboration sur les projets
    Route::get('/projects/{project}/collaborators', [ProjectCollaboratorController::class, 'index']);
    Route::post('/projects/{project}/collaborators', [ProjectCollaboratorController::class, 'store']);
    Route::put('/projects/{project}/collaborators/{user}', [ProjectCollaboratorController::class, 'update']);
    Route::delete('/projects/{project}/collaborators/{user}', [ProjectCollaboratorController::class, 'destroy']);
    Route::post('/projects/{project}/leave', [ProjectCollaboratorController::class, 'leave']);
    Route::get('/projects/{project}/search-users', [ProjectCollaboratorController::class, 'searchUsers']);

    // Invitations de projets
    Route::get('/invitations', [ProjectInvitationController::class, 'index']);
    Route::post('/invitations', [ProjectInvitationController::class, 'store']);
    Route::get('/projects/{project}/invitations', [ProjectInvitationController::class, 'projectInvitations']);
    Route::post('/invitations/{invitation}/accept', [ProjectInvitationController::class, 'accept']);
    Route::post('/invitations/{invitation}/decline', [ProjectInvitationController::class, 'decline']);
    Route::delete('/invitations/{invitation}', [ProjectInvitationController::class, 'destroy']);

    // Routes admin (middleware admin requis)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::post('/admin/users', [AdminUserController::class, 'store']);
        Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/admin/users/{user}', [AdminUserController::class, 'update']);
        Route::post('/admin/users/{user}/change-password', [AdminUserController::class, 'changePassword']);
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::get('/admin/projects', [AdminUserController::class, 'allProjects']);
        Route::delete('/admin/projects/{project}', [AdminUserController::class, 'destroyProject']);
        Route::get('/admin/stats', [AdminUserController::class, 'stats']);
    });
});
