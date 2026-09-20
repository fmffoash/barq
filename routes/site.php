<?php

use App\Http\Controllers\SiteController;
use App\Http\Middleware\DetectSite;
use Illuminate\Support\Facades\Route;

// معاينة أي موقع منشور — مسار عام (بدون تسجيل دخول، زي أي موقع حقيقي هيشوفه زوّار) تحت
// `/site/{siteSlug}` على نفس دومين لوحة التحكم بالظبط (2026-09-20، بدل سب دومين منفصل قديماً
// — راجع bootstrap/app.php). {siteSlug} بيتحدد من جزء المسار بدل الدومين، DetectSite
// نفسها متغيّرتش خالص.
Route::prefix('site')->middleware(DetectSite::class)->group(function () {
    Route::get('/{siteSlug}', [SiteController::class, 'show'])->name('site.show');
});
