<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * (2026-10-11، نفس اللي في طفرة) الجلسة بتتقفل فعلياً:
 * - بعد ربع ساعة من غير أي نشاط (`session.lifetime`) — بنسجّل آخر نشاط جوه الجلسة نفسها، لأن إعدادات الكوكي
 *   لوحدها مش مضمونة (المتصفح بيرجّع التابات بالكوكي، وقفل غطا اللابتوب مش إغلاق متصفح).
 * - لو اليوزر اتحظر أو اتوقف أو اتمسح وهو داخل — بيخرج في أول طلب بعدها.
 * طلبات بث الذكاء الاصطناعي الطويلة بتعدّي من غير ما تتقطع (الطلب نفسه نشاط).
 */
class EnsureSessionValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $lifetime = (int) config('session.lifetime') * 60;
            $lastSeen = $request->session()->get('idle_last_seen');

            $reason = null;
            // (2026-10-11 — فؤاد: جلسة الأدمن مقفلتش) كوكي «تذكّرني» قديم من قبل ما الخانة تتشال كان بيرجّع
            // الدخول لوحده بعد ما الجلسة تخلص — أي دخول عن طريقه مرفوض، ولازم الباسورد (والكود للأدمن)
            if (Auth::viaRemember()) {
                $reason = 'سجّل دخول تاني.';
            } elseif ($lifetime > 0 && $lastSeen && (time() - $lastSeen) > $lifetime) {
                $reason = 'الجلسة خلصت بعد ربع ساعة من غير نشاط — سجّل دخول تاني.';
            } elseif (! ($fresh = \App\Models\User::find($user->id)) || ! $fresh->canLogIn()) {
                // من قاعدة البيانات دلوقتي (مش النسخة اللي في الجلسة): الحظر بيسري في أول طلب
                $reason = $fresh?->blockedReason() ?? 'الحساب ده اتمسح.';
            }

            if ($reason) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson()
                    ? response()->json(['message' => $reason], 401)
                    : redirect()->route('login')->withErrors(['email' => $reason]);
            }

            $request->session()->put('idle_last_seen', time());
        }

        return $next($request);
    }
}
