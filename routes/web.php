<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeneratedSiteController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateSlotController;
use App\Http\Controllers\TemplateVariantController;
use Illuminate\Support\Facades\Route;

// أداة داخلية بمستخدم واحد بس — صفحة الدخول والخروج، والصفحة الرئيسية بعد الدخول
// هي لوحة التحكم مباشرة (مفيش صفحة تسويقية عامة).

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::resource('templates', TemplateController::class);

    Route::post('templates/{template}/variants', [TemplateVariantController::class, 'store'])
        ->name('templates.variants.store');
    Route::put('template-variants/{variant}', [TemplateVariantController::class, 'update'])
        ->name('template-variants.update');
    Route::delete('template-variants/{variant}', [TemplateVariantController::class, 'destroy'])
        ->name('template-variants.destroy');

    Route::post('templates/{template}/slots', [TemplateSlotController::class, 'store'])
        ->name('templates.slots.store');
    Route::put('template-slots/{slot}', [TemplateSlotController::class, 'update'])
        ->name('template-slots.update');
    Route::delete('template-slots/{slot}', [TemplateSlotController::class, 'destroy'])
        ->name('template-slots.destroy');

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/deliver', [ProjectController::class, 'deliver'])
        ->name('projects.deliver');
    Route::post('projects/{project}/save-as-template', [TemplateController::class, 'storeFromProject'])
        ->name('templates.store-from-project');

    Route::get('projects/{project}/site', [GeneratedSiteController::class, 'edit'])
        ->name('projects.site.edit');
    Route::put('projects/{project}/site', [GeneratedSiteController::class, 'update'])
        ->name('projects.site.update');
    Route::post('projects/{project}/site/suggest', [GeneratedSiteController::class, 'suggest'])
        ->name('projects.site.suggest');
    Route::post('projects/{project}/site/publish', [GeneratedSiteController::class, 'publish'])
        ->name('projects.site.publish');
    Route::post('projects/{project}/site/unpublish', [GeneratedSiteController::class, 'unpublish'])
        ->name('projects.site.unpublish');
});
