<?php

use App\Http\Controllers\SiteController;
use App\Http\Middleware\DetectSite;
use Illuminate\Support\Facades\Route;

// المسارات دي بتتسجّل بس تحت الدومين المطابق لـ {siteSlug}.{base_domain} (شوف الـ then
// closure في bootstrap/app.php) — يعني مفيش تعارض خالص مع مسارات لوحة التحكم في web.php.
Route::middleware(DetectSite::class)->group(function () {
    Route::get('/', [SiteController::class, 'show'])->name('site.show');
});
