<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;
Route::post('/videos/upload', [VideoController::class, 'upload'])->name('videos.upload');

