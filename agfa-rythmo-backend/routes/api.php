<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\VideoController;
Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

Route::apiResource('projects', ProjectController::class);

Route::get('/videos/{filename}', [VideoController::class, 'stream'])->where('filename', '.*');
