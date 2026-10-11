<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * تسجيل الدخول والخروج. اليوزرات بيتعملوا من شاشة إدارة اليوزرات (الأدمن بس) — مفيش تسجيل حسابات من برّه.
 *
 * (2026-10-11) طلب فؤاد:
 * - الأدمن بيدخل بخطوتين: الباسورد، وبعدين كود 6 أرقام بيوصل على إيميله (صالح 10 دقايق، 5 محاولات).
 * - المحظور والموقوف مابيدخلوش (والموقوف بيرجع لوحده بعد تاريخ الإيقاف).
 * - «تذكّرني» اتشالت: كانت بتحط كوكي طويل بيتخطّى قفل الجلسة بعد ربع ساعة.
 */
class AuthController extends Controller
{
    private const CODE_MINUTES = 10;

    private const CODE_ATTEMPTS = 5;

    // فورم تسجيل الدخول.
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'بيانات الدخول دي مش صحيحة.'])->onlyInput('email');
        }

        if (! $user->canLogIn()) {
            return back()->withErrors(['email' => $user->blockedReason()])->onlyInput('email');
        }

        if ($user->is_admin) {
            $this->sendCode($user);
            $request->session()->put('login_code_user', $user->id);

            return redirect()->route('login.code');
        }

        return $this->complete($request, $user);
    }

    // فورم كود الإيميل (الأدمن)
    public function code(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.login-code', ['email' => self::maskEmail($user->email)]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $code = (string) $request->validate(['code' => ['required', 'digits:6']])['code'];

        if (! $user->login_code_hash || ! $user->login_code_expires_at || $user->login_code_expires_at->isPast()) {
            return back()->withErrors(['code' => 'الكود خلص — دوس «ابعت كود جديد».']);
        }
        if ($user->login_code_attempts >= self::CODE_ATTEMPTS) {
            return back()->withErrors(['code' => 'محاولات كتير غلط — دوس «ابعت كود جديد».']);
        }
        if (! Hash::check($code, $user->login_code_hash)) {
            $user->forceFill(['login_code_attempts' => $user->login_code_attempts + 1])->save();

            return back()->withErrors(['code' => 'الكود مش صح.']);
        }

        $user->forceFill(['login_code_hash' => null, 'login_code_expires_at' => null, 'login_code_attempts' => 0])->save();
        $request->session()->forget('login_code_user');

        return $this->complete($request, $user);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $this->sendCode($user);

        return back()->with('status', 'اتبعت كود جديد على إيميلك.');
    }

    // تسجيل الخروج وإنهاء الجلسة.
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function complete(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('idle_last_seen', time());
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function sendCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $user->forceFill([
            'login_code_hash' => Hash::make($code),
            'login_code_expires_at' => now()->addMinutes(self::CODE_MINUTES),
            'login_code_attempts' => 0,
        ])->save();

        Mail::to($user->email)->send(new LoginCodeMail($code, self::CODE_MINUTES));
    }

    /** اليوزر اللي في خطوة الكود — ولسه مسموحله يدخل */
    private function pendingUser(Request $request): ?User
    {
        $user = User::find($request->session()->get('login_code_user'));

        return $user && $user->canLogIn() ? $user : null;
    }

    private static function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2).str_repeat('•', max(1, mb_strlen($name) - 2)).'@'.$domain;
    }
}
