<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiRunController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeneratedSiteController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SitePhotoController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateSlotController;
use App\Http\Controllers\TemplateVariantController;
use Illuminate\Support\Facades\Route;

// (2026-10-11) أدمن + يوزرات عادية (كل يوزر مشاريعه بس) — صفحة الدخول والخروج، والصفحة الرئيسية بعد الدخول
// هي لوحة التحكم مباشرة (مفيش صفحة تسويقية عامة).

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    // (2026-10-11) خطوة كود الإيميل للأدمن
    Route::get('/login/code', [AuthController::class, 'code'])->name('login.code');
    Route::post('/login/code', [AuthController::class, 'verifyCode'])->middleware('throttle:5,1')->name('login.code.verify');
    Route::post('/login/code/resend', [AuthController::class, 'resendCode'])->middleware('throttle:3,10')->name('login.code.resend');
});

// session.valid: قفل بعد ربع ساعة خمول + طرد المحظور/الموقوف (2026-10-11)
Route::middleware(['auth', 'session.valid'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // (2026-10-11) القوالب: أي يوزر يشوفها ويختار منها، والتعديل للأدمن بس (تحت)
    // الإنشاء/التعديل متسجّل الأول عشان templates/create ماتتلقطش كـ show
    Route::middleware('admin')->group(fn () => Route::resource('templates', TemplateController::class)->except(['index', 'show']));
    Route::resource('templates', TemplateController::class)->only(['index', 'show']);
    // معاينة كاملة لشكل القالب الحقيقي قبل اختياره (TemplatePreviewService) — صفحة واحدة بس
    // وقت ما فؤاد يدوس "معاينة"، مش iframe لكل كارت (شوف CLAUDE.md).
    Route::get('templates/{template}/preview', [TemplateController::class, 'preview'])
        ->name('templates.preview');

    Route::middleware('admin')->post('templates/{template}/variants', [TemplateVariantController::class, 'store'])
        ->name('templates.variants.store');
    Route::middleware('admin')->put('template-variants/{variant}', [TemplateVariantController::class, 'update'])
        ->name('template-variants.update');
    Route::middleware('admin')->delete('template-variants/{variant}', [TemplateVariantController::class, 'destroy'])
        ->name('template-variants.destroy');

    Route::middleware('admin')->post('templates/{template}/slots', [TemplateSlotController::class, 'store'])
        ->name('templates.slots.store');
    Route::middleware('admin')->put('template-slots/{slot}', [TemplateSlotController::class, 'update'])
        ->name('template-slots.update');
    Route::middleware('admin')->delete('template-slots/{slot}', [TemplateSlotController::class, 'destroy'])
        ->name('template-slots.destroy');

    Route::get('ai', [AiChatController::class, 'create'])->name('ai-chat.create');
    Route::post('ai', [AiChatController::class, 'store'])->name('ai-chat.store');
    Route::get('projects/{project}/ai', [AiChatController::class, 'show'])->name('ai-chat.show');
    Route::post('projects/{project}/ai', [AiChatController::class, 'message'])->name('ai-chat.message');

    // طلبات الذكاء الاصطناعي بالعدّاد والبث (AiRunController) — نفس الأفعال اللي فوق بالظبط،
    // بس المتصفح بيشوف الرد وهو بيتكتب والوقت الباقي. الفورمز اللي فوق فاضلة للحالة اللي
    // الجافاسكريبت مش شغال فيها.
    Route::post('ai/runs', [AiRunController::class, 'start'])->name('ai-runs.start');
    Route::post('ai/runs/{run}/stream', [AiRunController::class, 'stream'])->name('ai-runs.stream');
    Route::post('ai/runs/{run}/server', [AiRunController::class, 'server'])->name('ai-runs.server');
    Route::post('ai/runs/{run}/complete', [AiRunController::class, 'complete'])->name('ai-runs.complete');
    Route::post('ai/runs/{run}/cancel', [AiRunController::class, 'cancel'])->name('ai-runs.cancel');

    // إعدادات الذكاء الاصطناعي (2026-10-10): الحالة، النموذج، حجم السياق، السرعة الحقيقية.
    // إدارة اليوزرات (2026-10-11) — الأدمن بس
    Route::middleware('admin')->group(function (): void {
        Route::get('users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}/password', [\App\Http\Controllers\UserController::class, 'password'])->name('users.password');
        Route::put('users/{user}/status', [\App\Http\Controllers\UserController::class, 'status'])->name('users.status');
        Route::delete('users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('admin')->get('settings/ai', [AiSettingsController::class, 'show'])->name('ai-settings.show');
    Route::middleware('admin')->put('settings/ai', [AiSettingsController::class, 'update'])->name('ai-settings.update');
    Route::middleware('admin')->post('settings/ai/benchmark', [AiSettingsController::class, 'benchmark'])->name('ai-settings.benchmark');

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/deliver', [ProjectController::class, 'deliver'])
        ->name('projects.deliver');
    Route::middleware('admin')->post('projects/{project}/save-as-template', [TemplateController::class, 'storeFromProject'])
        ->name('templates.store-from-project');

    Route::get('projects/{project}/site', [GeneratedSiteController::class, 'edit'])
        ->name('projects.site.edit');
    Route::put('projects/{project}/site', [GeneratedSiteController::class, 'update'])
        ->name('projects.site.update');
    // محرر بصري مباشر (WYSIWYG click-to-edit) — لازم يفضل جوّه مجموعة auth دي بالظبط،
    // صفر إضافة أي query parameter على `routes/site.php` العام لتفعيل نفس الميزة دي
    // (راجع "قيد أمان إجباري" في docs/wysiwyg-editor-plan.md).
    Route::get('projects/{project}/site/live-edit', [GeneratedSiteController::class, 'liveEdit'])
        ->name('projects.site.live-edit');
    Route::post('projects/{project}/site/suggest', [GeneratedSiteController::class, 'suggest'])
        ->name('projects.site.suggest');
    // "✨ صياغة تانية" لخانة نص واحدة من المحرر المباشر — المسار ده للحالة اللي الجافاسكريبت
    // مش شغال فيها (العادي: AiRunController بنوع rewrite بالعدّاد).
    Route::post('projects/{project}/site/rewrite', [GeneratedSiteController::class, 'rewrite'])
        ->name('projects.site.rewrite');
    // مخزن صور المشروع (SitePhotoController) — إضافة صور، حط صورة في خانة، شيل صورة.
    Route::post('projects/{project}/site/photos', [SitePhotoController::class, 'store'])
        ->name('projects.site.photos.store');
    Route::post('projects/{project}/site/photos/use', [SitePhotoController::class, 'use'])
        ->name('projects.site.photos.use');
    Route::delete('projects/{project}/site/photos', [SitePhotoController::class, 'destroy'])
        ->name('projects.site.photos.destroy');
    Route::post('projects/{project}/site/publish', [GeneratedSiteController::class, 'publish'])
        ->name('projects.site.publish');
    Route::post('projects/{project}/site/unpublish', [GeneratedSiteController::class, 'unpublish'])
        ->name('projects.site.unpublish');
    Route::get('projects/{project}/site/export', [GeneratedSiteController::class, 'export'])
        ->name('projects.site.export');
    Route::post('projects/{project}/site/provision-wordpress', [GeneratedSiteController::class, 'provisionWordPress'])
        ->name('projects.site.provision-wordpress');
    Route::post('projects/{project}/site/push-wordpress-content', [GeneratedSiteController::class, 'pushWordPressContent'])
        ->name('projects.site.push-wordpress-content');
});
