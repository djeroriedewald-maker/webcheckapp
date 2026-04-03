<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'index'])->name('home');
Route::get('/scan', fn () => redirect('/'))->name('scan.index');
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
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Checkout (auth only)
Route::middleware('auth')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Stripe webhook (no CSRF)
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

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

// Admin
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\AdminOnly::class])
    ->name('admin');

// Sitemap
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'),             'changefreq' => 'daily',   'priority' => '1.0'],
        ['loc' => url('/compare'),      'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => url('/api'),          'changefreq' => 'monthly', 'priority' => '0.6'],
        ['loc' => url('/disclaimer'),   'changefreq' => 'yearly',  'priority' => '0.3'],
        ['loc' => url('/privacy'),      'changefreq' => 'yearly',  'priority' => '0.3'],
        ['loc' => url('/terms'),        'changefreq' => 'yearly',  'priority' => '0.3'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url['loc']}</loc>\n";
        $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
