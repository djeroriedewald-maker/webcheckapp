<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'index'])->name('home');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store')->middleware('throttle:10,1');
Route::get('/scan/{scan}', [ScanController::class, 'show'])->name('scan.show');
Route::get('/scan/{scan}/status', [ScanController::class, 'status'])->name('scan.status');
Route::get('/scan/{scan}/card', [ScanController::class, 'card'])->name('scan.card');
Route::get('/scan/{scan}/pdf', [ScanController::class, 'pdf'])->name('scan.pdf')->middleware('throttle:10,1');
Route::get('/scan/{scan}/badge', [ScanController::class, 'badge'])->name('scan.badge')->middleware('throttle:60,1');

// Compare two domains side by side
Route::get('/compare', [ScanController::class, 'compare'])->name('scan.compare')->middleware('throttle:5,1');

// Public JSON API — rate-limited to 5 requests per minute
Route::get('/api/v1/scan', [ApiController::class, 'scan'])->name('api.scan')->middleware('throttle:5,1');
Route::get('/api', [ApiController::class, 'docs'])->name('api.docs');

// Auth routes (guests only)
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard (auth only)
Route::middleware('auth')->prefix('dashboard')->name('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/sites', [DashboardController::class, 'addSite'])->name('.addSite');
    Route::post('/sites/bulk', [DashboardController::class, 'bulkImport'])->name('.bulkImport');
    Route::delete('/sites/{site}', [DashboardController::class, 'removeSite'])->name('.removeSite');
    Route::post('/sites/{site}/refresh', [DashboardController::class, 'refreshSite'])->name('.refresh')->middleware('throttle:5,1');
    Route::patch('/sites/{site}/notifications', [DashboardController::class, 'updateNotifications'])->name('.notifications');
    Route::get('/history/{domain}', [DashboardController::class, 'history'])->name('.history')->where('domain', '.*');
});

Route::view('/disclaimer', 'legal.disclaimer')->name('disclaimer');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
