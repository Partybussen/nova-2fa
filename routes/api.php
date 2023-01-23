<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Partybussen\Nova2fa\Http\Controllers\Nova2faController;
/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::post('/verify', [Nova2faController::class, 'verify'])->name('nova2fa.verify');
Route::post('/confirm', [Nova2faController::class, 'confirmRegistration'])->name('nova2fa.confirm');
Route::post('/recover', [Nova2faController::class, 'checkRecovery'])->name('nova2fa.recover');