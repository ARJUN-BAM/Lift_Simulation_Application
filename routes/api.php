<?php

use App\Http\Controllers\LiftController;
use Illuminate\Support\Facades\Route;

Route::post('/lifts', [LiftController::class, 'requestLift']);
Route::post('/lifts/{id}', [LiftController::class, 'insideLift']);
Route::post('/lifts/{id}/cancel', [LiftController::class, 'cancelLift']);
Route::get('/lifts/all-lifts', [LiftController::class, 'getAllLifts']);
Route::put('/lifts', [LiftController::class, 'resetLifts']);

// Route::view("/", "welcome");
