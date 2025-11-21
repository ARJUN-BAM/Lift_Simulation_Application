<?php

use App\Http\Controllers\LiftController;
use Illuminate\Support\Facades\Route;

Route::post('/lifts', [LiftController::class, 'requestLift']);
Route::post('/lifts/{id}', [LiftController::class, 'insideLift']);
Route::get('/lifts/status', [LiftController::class, 'status']);
Route::post('/lifts/{id}/cancel',[LiftController::class,'cancelLift']);
