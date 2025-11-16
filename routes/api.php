<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserContorller;
use App\Http\Controllers\WasteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::middleware('auth:sanctum')->group(function () {
   Route::get('/user',[UserContorller::class,'show']);
   Route::get('/wastedetail',[UserContorller::class,'wastes']);

    Route::post('/wastes', [WasteController::class, 'store'])->name('waste.store');
    Route::post('/logout', [AuthController::class, 'logout']);
});
