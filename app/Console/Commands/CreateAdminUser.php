<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

// بيعمل أو يحدّث حساب الأدمن الوحيد في النظام. بياخد الباسورد تفاعلياً وقت التشغيل
// (secret prompt) وميكتبهوش في أي ملف أو لوج — الالتزام الصارم بقاعدة عدم تخزين
// أي باسورد حقيقي في المشروع.
//
// ويندوز (2026-10-06): السؤال المخفي بتاع Symfony بيشغّل hiddeninput.exe، واللي محتاج Visual C++
// 2008 runtime (MSVCR90.dll) — مش موجود على ويندوز 10/11 نضيف، فالباسورد كان بيرجع فاضي
// والحساب مبيتعملش خالص. وحتى لو موجود، بيقرا بترميز الكونسول مش UTF-8 (باسورد بحروف عربي
// بيتخزّن غلط ومبيعرفش يدخل بيه من المتصفح). فعلى ويندوز الأسئلة بتتسأل من PowerShell
// (local/create-admin.ps1 و setup.ps1 — Read-Host بيقرا Unicode صح) وبتتبعت هنا على stdin
// (`--stdin`): 3 سطور base64 لنص UTF-8 (الاسم/الإيميل/الباسورد) — مفيش ملف ولا متغيّر بيئة.
#[Signature('barq:create-admin
    {--stdin : Read name, email and password from standard input (three base64-encoded UTF-8 lines) - used by the Windows scripts}
    {--check : Only report whether an admin account exists (exit code 0 = yes)}')]
#[Description('إنشاء أو تحديث حساب الأدمن الوحيد في النظام')]
class CreateAdminUser extends Command
{
    private const DEFAULT_NAME = 'الأدمن';

    public function handle(): int
    {
        if ($this->option('check')) {
            $exists = User::query()->exists();
            $this->line($exists ? 'An admin account exists.' : 'No admin account yet.');

            return $exists ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('stdin')) {
            $values = $this->readFromStdin();
            if ($values === null) {
                $this->error('Expected three base64-encoded lines on standard input: name, email, password.');

                return self::FAILURE;
            }

            [$name, $email, $password] = $values;
            $passwordConfirmation = $password;
        } elseif (windows_os()) {
            $this->error('On Windows, create or change the login with local\\create-admin.bat instead (the hidden password prompt does not work there).');

            return self::FAILURE;
        } else {
            // كل سؤال بالإنجليزي جنب العربي (2026-10-05): طرفيات كتير مبتدعمش اتجاه النص من اليمين
            // للشمال، فالعربي لوحده بيطلع مقلوب ومش مقروء وقت التجهيز المحلي (barq:local-setup).
            $name = $this->ask('Name / الاسم', self::DEFAULT_NAME);
            $email = $this->ask('Email / البريد الإلكتروني');
            $password = $this->secret('Password, 8+ characters / كلمة المرور (٨ حروف على الأقل)');
            $passwordConfirmation = $this->secret('Confirm password / تأكيد كلمة المرور');
        }

        $validator = Validator::make(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // اسم فاضي (Enter) = سيب الاسم الحالي لو الحساب موجود (تغيير باسورد)، أو الافتراضي لو جديد.
        $name = trim((string) $name);
        if ($name === '') {
            $name = User::where('email', $email)->value('name') ?? self::DEFAULT_NAME;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $this->info("تم حفظ حساب الأدمن بنجاح: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function readFromStdin(): ?array
    {
        // rtrim مش trim: الاسم ممكن يبقى فاضي (Enter = الاسم الافتراضي)، وbase64 لنص فاضي سطر فاضي —
        // trim كان بيشيله ويبقوا سطرين بس.
        $lines = preg_split('/\R/', rtrim((string) stream_get_contents(STDIN), "\r\n"));

        if (! is_array($lines) || count($lines) < 3) {
            return null;
        }

        $values = [];
        foreach (array_slice($lines, 0, 3) as $line) {
            $decoded = base64_decode(trim($line), true);
            if ($decoded === false || ! mb_check_encoding($decoded, 'UTF-8')) {
                return null;
            }
            $values[] = $decoded;
        }

        return [$values[0], trim($values[1]), $values[2]];
    }
}
