<?php

// routes/web.php

use App\Http\Controllers\RouteOptimizationController;
use Illuminate\Support\Facades\Route;


// Route optimization routes (protected by auth middleware)
Route::middleware(['auth'])->prefix('admin/pickups')->name('admin.pickups.')->group(function () {
    Route::post('/optimize-route', [RouteOptimizationController::class, 'optimizeFromLocation'])
        ->name('optimize-route');
    
    Route::get('/pending', [RouteOptimizationController::class, 'getPendingPickups'])
        ->name('pending');
});