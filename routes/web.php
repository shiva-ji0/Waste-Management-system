<?php

use App\Http\Controllers\RouteOptimizationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Route Optimization Routes (Protected by Filament Auth)
Route::middleware(['web', 'auth'])->prefix('admin/pickups')->group(function () {
    Route::post('/optimize-route', [RouteOptimizationController::class, 'optimizeFromLocation'])
        ->name('pickups.optimize-route');

    Route::get('/pickups', [RouteOptimizationController::class, 'getPickups'])
        ->name('pickups.get');

    Route::post('/calculate-distance', [RouteOptimizationController::class, 'calculateDistance'])
        ->name('pickups.calculate-distance');
});
