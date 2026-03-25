<?php

use Illuminate\Support\Facades\Route;
use Src\AccessControl\Interfaces\Http\RegisterCheckInController;
use Src\Engagement\Interfaces\Http\GetUserDashboardCheckInsController;

Route::post('/check-ins', RegisterCheckInController::class);
Route::get('/dashboard/{userId}/check-ins', GetUserDashboardCheckInsController::class);