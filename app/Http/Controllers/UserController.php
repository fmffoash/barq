<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * إدارة اليوزرات (2026-10-11) — للأدمن بس (ميدل وير admin).
 *
 * ⚠️ الباسورد مابيتعرضش أبداً: بيتخزّن متشفّر بطريقة مابترجعش، فمحدش يقدر يشوفه. البديل: الأدمن يحط باسورد
 * جديد بنفسه، أو يولّد باسورد مؤقت بيظهر له **مرة واحدة** يبعته لليوزر.
 *
 * حماية: الأدمن مايقدرش يحظر/يوقف/يمسح نفسه، ولازم يفضل أدمن واحد شغال على الأقل. مسح يوزر بيخلّي مشاريعه
 * للأدمن اللي مسحه (صفر ضياع شغل).
 */
class UserController extends Controller
{
    public function index(): View
    {
        $users = User::withCount('projects')->orderByDesc('is_admin')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $generated = blank($data['password'] ?? null) ? self::generatePassword() : null;
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $generated ?? $data['password']]);
        $user->forceFill(['is_admin' => (bool) ($data['is_admin'] ?? false), 'status' => User::ACTIVE])->save();

        return redirect()->route('users.index')
            ->with('status', "اتعمل اليوزر «{$user->name}».")
            ->with('generated_password', $generated ? ['name' => $user->name, 'password' => $generated] : null);
    }

    public function password(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['nullable', 'string', 'min:8', 'max:100']]);
        $generated = blank($data['password'] ?? null) ? self::generatePassword() : null;

        $user->forceFill(['password' => $generated ?? $data['password']])->save();
        // أي جلسة شغالة بالباسورد القديم بتقفل
        DB::table('sessions')->where('user_id', $user->id)->when($user->id === auth()->id(), fn ($q) => $q->where('id', '!=', session()->getId()))->delete();

        return back()
            ->with('status', "اتغيّر باسورد «{$user->name}».")
            ->with('generated_password', $generated ? ['name' => $user->name, 'password' => $generated] : null);
    }

    /** تعديل بيانات اليوزر كلها: الاسم والإيميل (اللي بيدخل بيه) والدور (2026-10-11) */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'is_admin' => ['nullable', 'boolean'],
        ]);
        $makeAdmin = (bool) ($data['is_admin'] ?? false);

        // شيل دور الأدمن من نفسك أو من آخر أدمن = حد يتقفل برّه إدارة اليوزرات
        if ($user->is_admin && ! $makeAdmin && ($error = $this->guardSelfAndLastAdmin($user))) {
            return back()->withErrors(['user' => $error]);
        }

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        $user->forceFill(['is_admin' => $makeAdmin])->save();

        return back()->with('status', "اتحفظت بيانات «{$user->name}».");
    }

    /**
     * إيقاف / تفعيل (2026-10-11 — فؤاد): الإيقاف ثابت لحد ما الأدمن يشغّله تاني (من غير مدة)، والحظر اتشال.
     */
    public function status(Request $request, User $user): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', Rule::in(['activate', 'suspend'])]])['action'];

        if ($action === 'suspend' && ($error = $this->guardSelfAndLastAdmin($user))) {
            return back()->withErrors(['user' => $error]);
        }

        $user->forceFill([
            'status' => $action === 'suspend' ? User::SUSPENDED : User::ACTIVE,
            'suspended_until' => null,
        ])->save();

        if ($action === 'suspend') {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // بيخرج حالاً
        }

        return back()->with('status', "«{$user->name}» ".($action === 'suspend' ? 'اتوقف — مش هيقدر يدخل لحد ما تشغّله.' : 'اتشغّل.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($error = $this->guardSelfAndLastAdmin($user)) {
            return back()->withErrors(['user' => $error]);
        }

        DB::transaction(function () use ($user) {
            Project::where('created_by', $user->id)->update(['created_by' => auth()->id()]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return back()->with('status', "اتمسح «{$user->name}»، ومشاريعه بقت عندك.");
    }

    private function guardSelfAndLastAdmin(User $user): ?string
    {
        if ($user->id === auth()->id()) {
            return 'مينفعش تعمل كده في حسابك إنت.';
        }
        if ($user->is_admin && User::where('is_admin', true)->where('status', User::ACTIVE)->where('id', '!=', $user->id)->doesntExist()) {
            return 'لازم يفضل أدمن واحد شغال على الأقل.';
        }

        return null;
    }

    private static function generatePassword(): string
    {
        // من غير حروف بتتلخبط (0/O، 1/l/I)
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, 12))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
    }
}
