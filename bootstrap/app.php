<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // معاينة أي موقع منشور بقت مسار (`/site/{siteSlug}`) تحت نفس دومين لوحة التحكم
            // نفسه، مش سب دومين منفصل (2026-09-20 — فؤاد طلب صراحة إن المعاينة تفضل "جوّه
            // نفس السب دومين" زي تاب عادي، عشان يتجنّب الحاجة لشهادة SSL مدفوعة من Cloudflare
            // لتغطية مستوى wildcard تاني).
            Route::group([], base_path('routes/site.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // كل مسارات النظام محمية بالدخول عدا صفحة اللوجين — فأي زائر مش داخل بيتوجّه
        // للوجين تلقائي، وأي حد داخل بالفعل بيتوجّه للوحة التحكم لو حاول يفتح صفحة زوّار.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
