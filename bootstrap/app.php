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
            // أي طلب على subdomain.{base_domain} (زي مطعم-الطعمية.barq.tafraos.com) بيروح
            // لمسارات routes/site.php بدل مسارات لوحة التحكم — {siteSlug} بيتحط تلقائي من
            // نفس الدومين. طلب على دومين تاني (localhost وقت التطوير، أو دومين لوحة التحكم
            // نفسها) بيفضل يعدّي على مسارات web.php العادية زي ما هو.
            Route::domain('{siteSlug}.'.config('barq.base_domain'))
                ->group(base_path('routes/site.php'));
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
