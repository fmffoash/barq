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

    public function status(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'block', 'suspend'])],
            'days' => ['required_if:action,suspend', 'nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if ($data['action'] !== 'activate' && ($error = $this->guardSelfAndLastAdmin($user))) {
            return back()->withErrors(['user' => $error]);
        }

        $fill = match ($data['action']) {
            'activate' => ['status' => User::ACTIVE, 'suspended_until' => null],
            'block' => ['status' => User::BLOCKED, 'suspended_until' => null],
            'suspend' => ['status' => User::SUSPENDED, 'suspended_until' => now()->addDays((int) $data['days'])],
        };
        $user->forceFill($fill)->save();

        if ($data['action'] !== 'activate') {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // بيخرج حالاً
        }

        $label = ['activate' => 'اتفعّل', 'block' => 'اتحظر', 'suspend' => 'اتوقف مؤقتاً لحد '.$user->suspended_until?->timezone('Africa/Cairo')->format('Y-m-d')][$data['action']];

        return back()->with('status', "«{$user->name}» {$label}.");
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
