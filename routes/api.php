<?php

use Illuminate\Support\Facades\Route;
use Src\AccessControl\Interfaces\Http\RegisterCheckInController;

Route::post('/check-ins', RegisterCheckInController::class);
