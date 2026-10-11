<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// (2026-10-11) شاشات الأدمن بس: إدارة اليوزرات، وتعديل القوالب، وإعدادات الذكاء الاصطناعي.
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403, 'الصفحة دي للأدمن بس.');

        return $next($request);
    }
}
