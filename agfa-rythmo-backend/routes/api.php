<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\VideoController;
Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

Route::apiResource('projects', ProjectController::class);


use App\Http\Controllers\SceneChangeController;
// Changements de plan (scene changes)
Route::get('/projects/{project}/scene-changes', [SceneChangeController::class, 'index']);
Route::post('/projects/{project}/scene-changes', [SceneChangeController::class, 'store']);
Route::delete('/scene-changes/{id}', [SceneChangeController::class, 'destroy']);

Route::get('/videos/{filename}', [VideoController::class, 'stream'])->where('filename', '.*');
