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
#[Signature('barq:create-admin')]
#[Description('إنشاء أو تحديث حساب الأدمن الوحيد في النظام')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $name = $this->ask('الاسم', 'الأدمن');
        $email = $this->ask('البريد الإلكتروني');
        $password = $this->secret('كلمة المرور (٨ حروف على الأقل)');
        $passwordConfirmation = $this->secret('تأكيد كلمة المرور');

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
}
