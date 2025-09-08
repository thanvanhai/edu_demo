<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CameraController;

Route::get('/', function () {
    // return view('welcome');
    return redirect('/admin');
});

// Camera management routes
Route::get('/cameras', [CameraController::class, 'index'])->name('cameras.index');

// API routes for camera management
Route::prefix('api/cameras')->group(function () {
    Route::get('/', [CameraController::class, 'getCameras']);
    Route::post('/', [CameraController::class, 'store']);
    Route::put('/{id}', [CameraController::class, 'update']);
    Route::delete('/{id}', [CameraController::class, 'destroy']);
    Route::post('/{id}/test', [CameraController::class, 'testCamera']);
    Route::get('/test-connection', [CameraController::class, 'testConnection']);
    Route::get('/statistics', [CameraController::class, 'getStatistics']);
});