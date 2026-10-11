<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// (2026-10-11) طلب فؤاد: يوزرات متعددة، كل يوزر مشاريعه بس، إدارة اليوزرات للأدمن، كود إيميل لدخول الأدمن،
// حظر/إيقاف مؤقت، وقفل الجلسة بعد ربع ساعة خمول.
class UsersAndAccessTest extends TestCase
{
    use RefreshDatabase;

    // ───────── كود الإيميل للأدمن ─────────

    public function test_admin_needs_the_email_code_to_log_in(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['password' => Hash::make('secret-pass')]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-pass'])->assertRedirect(route('login.code'));
        $this->assertGuest();

        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $mail) use ($admin, &$code) {
            $code = $mail->code;

            return $mail->hasTo($admin->email);
        });
        $this->assertNotSame($code, $admin->fresh()->login_code_hash, 'الكود متخزّن متشفّر');

        $this->post('/login/code', ['code' => $code === '111111' ? '222222' : '111111'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/code', ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertNull($admin->fresh()->login_code_hash, 'الكود بيتمسح بعد الاستخدام');
    }

    public function test_code_expires_and_locks_after_too_many_attempts(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['password' => Hash::make('secret-pass')]);
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-pass']);

        $admin->forceFill(['login_code_attempts' => 5])->save();
        $sent = null;
        Mail::assertSent(LoginCodeMail::class, function ($m) use (&$sent) { $sent = $m->code; return true; });
        $this->post('/login/code', ['code' => $sent])->assertSessionHasErrors('code');
        $this->assertGuest();

        $admin->forceFill(['login_code_attempts' => 0, 'login_code_expires_at' => now()->subMinute()])->save();
        $this->post('/login/code', ['code' => $sent])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_code_step_without_password_first_goes_back_to_login(): void
    {
        $this->get('/login/code')->assertRedirect(route('login'));
        $this->post('/login/code', ['code' => '123456'])->assertRedirect(route('login'));
    }

    // ───────── حظر / إيقاف ─────────

    // (2026-10-11 — فؤاد) الإيقاف ثابت لحد ما الأدمن يشغّله، والحظر اتشال
    public function test_suspended_users_cannot_log_in_until_reactivated_and_are_kicked_out(): void
    {
        $user = User::factory()->member()->create(["password" => Hash::make("secret-pass")]);

        $user->forceFill(["status" => User::SUSPENDED, "suspended_until" => null])->save();
        $this->post("/login", ["email" => $user->email, "password" => "secret-pass"])->assertSessionHasErrors("email");
        $this->assertGuest();

        $user->forceFill(["status" => User::ACTIVE])->save();
        $this->post("/login", ["email" => $user->email, "password" => "secret-pass"])->assertRedirect(route("admin.dashboard"));
        $this->assertAuthenticatedAs($user);

        // اتوقف وهو داخل = بيخرج في أول طلب
        $user->forceFill(["status" => User::SUSPENDED])->save();
        $this->get("/")->assertRedirect(route("login"));
        $this->assertGuest();
    }

    public function test_session_expires_after_fifteen_idle_minutes(): void
    {
        config(['session.lifetime' => 15]); // على السيرفر من .env (SESSION_LIFETIME=15)
        $user = User::factory()->member()->create();

        $this->actingAs($user)->withSession(['idle_last_seen' => time() - 16 * 60])->get('/')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($user)->withSession(['idle_last_seen' => time() - 5 * 60])->get('/')->assertOk();
    }

    // ───────── كل يوزر مشاريعه بس ─────────

    public function test_members_see_and_reach_only_their_own_projects(): void
    {
        $alice = User::factory()->member()->create();
        $bob = User::factory()->member()->create();
        $admin = User::factory()->create();
        $mine = Project::factory()->create(['name' => 'مشروع أليس', 'created_by' => $alice->id]);
        $theirs = Project::factory()->create(['name' => 'مشروع بوب', 'created_by' => $bob->id]);

        $this->actingAs($alice)->get(route('projects.index'))->assertOk()->assertSee('مشروع أليس')->assertDontSee('مشروع بوب');
        $this->actingAs($alice)->get(route('projects.show', $mine))->assertOk();
        $this->actingAs($alice)->get(route('projects.show', $theirs))->assertNotFound();
        $this->actingAs($alice)->get(route('projects.site.edit', $theirs))->assertNotFound();
        $this->actingAs($alice)->delete(route('projects.destroy', $theirs))->assertNotFound();
        $this->assertNotNull($theirs->fresh());

        // طلب ذكاء اصطناعي برقم مشروع حد تاني
        $this->actingAs($alice)->postJson(route('ai-runs.start'), ['kind' => 'follow_up', 'project_id' => $theirs->id, 'message' => 'غير اللون'])->assertNotFound();

        // الأدمن بيشوف الكل
        $this->actingAs($admin)->get(route('projects.index'))->assertSee('مشروع أليس')->assertSee('مشروع بوب');
    }

    public function test_members_cannot_reach_admin_screens(): void
    {
        $member = User::factory()->member()->create();
        $template = Template::factory()->create();

        $this->actingAs($member)->get(route('users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('ai-settings.show'))->assertForbidden();
        $this->actingAs($member)->get(route('templates.create'))->assertForbidden();
        $this->actingAs($member)->delete(route('templates.destroy', $template))->assertForbidden();
        $this->actingAs($member)->get(route('templates.index'))->assertOk()->assertDontSee('+ قالب جديد');
        $this->assertNotNull($template->fresh());
    }

    // ───────── إدارة اليوزرات ─────────

    public function test_admin_manages_users(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        // يوزر جديد بباسورد متولّد بيظهر مرة واحدة
        $this->post(route('users.store'), ['name' => 'موظف', 'email' => 'staff@example.com'])->assertSessionHas('generated_password');
        $staff = User::where('email', 'staff@example.com')->firstOrFail();
        $this->assertFalse($staff->is_admin);
        $generated = session('generated_password')['password'];
        $this->assertTrue(Hash::check($generated, $staff->password));

        $this->get(route('users.index'))->assertOk()->assertSee('موظف')->assertDontSee($staff->password);

        // تغيير الباسورد
        $this->put(route('users.password', $staff), ['password' => 'new-strong-pass'])->assertRedirect();
        $this->assertTrue(Hash::check('new-strong-pass', $staff->fresh()->password));

        // تعديل البيانات كلها
        $this->put(route('users.update', $staff), ['name' => 'موظف جديد', 'email' => 'staff2@example.com', 'is_admin' => '1'])->assertRedirect();
        $this->assertSame('staff2@example.com', $staff->fresh()->email);
        $this->assertTrue($staff->fresh()->is_admin);
        $this->put(route('users.update', $staff), ['name' => 'موظف جديد', 'email' => $admin->email, 'is_admin' => '0'])->assertSessionHasErrors('email');
        $this->put(route('users.update', $staff), ['name' => 'موظف جديد', 'email' => 'staff2@example.com', 'is_admin' => '0']);
        $this->assertFalse($staff->fresh()->is_admin);

        // إيقاف (من غير مدة) / تشغيل — الحظر اتشال
        $this->put(route('users.status', $staff), ['action' => 'suspend']);
        $this->assertFalse($staff->fresh()->canLogIn());
        $this->assertNull($staff->fresh()->suspended_until);
        $this->put(route('users.status', $staff), ['action' => 'block'])->assertSessionHasErrors('action');
        $this->put(route('users.status', $staff), ['action' => 'activate']);
        $this->assertTrue($staff->fresh()->canLogIn());

        // المسح بينقل مشاريعه للأدمن
        $project = Project::factory()->create(['created_by' => $staff->id]);
        $this->delete(route('users.destroy', $staff))->assertRedirect();
        $this->assertNull($staff->fresh());
        $this->assertSame($admin->id, (int) $project->fresh()->created_by);
    }

    public function test_admin_cannot_lock_out_himself_or_the_last_admin(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->put(route('users.status', $admin), ['action' => 'suspend'])->assertSessionHasErrors('user');
        $this->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_admin' => '0'])->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_admin);
        $this->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');
        $this->assertSame(User::ACTIVE, $admin->fresh()->status);
    }
}
