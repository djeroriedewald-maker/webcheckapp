<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'index'])->name('home');
Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');
Route::get('/scan/{scan}', [ScanController::class, 'show'])->name('scan.show');
Route::get('/scan/{scan}/status', [ScanController::class, 'status'])->name('scan.status');
