<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

Route::get('/', [ImageController::class, 'index']);
Route::post('/crop', [ImageController::class, 'crop'])->name('crop');
