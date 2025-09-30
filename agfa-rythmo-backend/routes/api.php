<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\SceneChangeController;
use App\Http\Controllers\TimecodeController;
use App\Http\Controllers\CharacterController;

Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

Route::apiResource('projects', ProjectController::class);

// Mise à jour du nombre de lignes rythmo
Route::patch('/projects/{project}/rythmo-lines', [ProjectController::class, 'updateRythmoLinesCount']);

// Changements de plan (scene changes)
Route::get('/projects/{project}/scene-changes', [SceneChangeController::class, 'index']);
Route::post('/projects/{project}/scene-changes', [SceneChangeController::class, 'store']);
Route::put('/scene-changes/{id}', [SceneChangeController::class, 'update']);
Route::delete('/scene-changes/{id}', [SceneChangeController::class, 'destroy']);

// Timecodes multi-lignes
Route::get('/projects/{project}/timecodes', [TimecodeController::class, 'index']);
Route::post('/projects/{project}/timecodes', [TimecodeController::class, 'store']);
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
Route::get('/characters/for-cloning', [CharacterController::class, 'getForCloning']);

Route::get('/videos/{filename}', [VideoController::class, 'stream'])->where('filename', '.*');
