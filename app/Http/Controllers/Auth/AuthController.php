<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// تسجيل الدخول والخروج. النظام ده بمستخدم واحد بس (الأدمن) — فمفيش تسجيل حسابات جديدة
// ولا استرجاع باسورد هنا؛ حساب الأدمن بيتعمل من الطرفية بالأمر: php artisan barq:create-admin
class AuthController extends Controller
{
    // فورم تسجيل الدخول.
    public function create(): View
    {
        return view('auth.login');
    }

    // التحقق من بيانات الدخول وبدء الجلسة.
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'بيانات الدخول دي مش صحيحة.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    // تسجيل الخروج وإنهاء الجلسة.
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
