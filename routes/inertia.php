<?php

use Illuminate\Support\Facades\Route;
use Partybussen\Nova2fa\Http\Controllers\Nova2faController;

/*
|--------------------------------------------------------------------------
| Tool Routes
|--------------------------------------------------------------------------
|
| Here is where you may register Inertia routes for your tool. These are
| loaded by the ServiceProvider of the tool. The routes are protected
| by your tool's "Authorize" middleware by default. Now - go build!
|
*/

Route::get('/', [Nova2faController::class, 'showAuthenticate'])->name('nova2fa.index');
Route::get('recover', [Nova2faController::class, 'showRecovery'])->name('nova2fa.recover');
Route::get('register', [Nova2faController::class, 'showRegister'])->name('nova2fa.register');
