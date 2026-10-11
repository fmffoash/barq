<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// (2026-10-11) أدمن + يوزرات عادية. الدور والحالة وكود الدخول بره fillable عمداً — بيتحطوا من
// شاشة إدارة اليوزرات (الأدمن بس) أو من الدخول، بـ forceFill.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'login_code_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ACTIVE = 'active';
    public const BLOCKED = 'blocked';
    public const SUSPENDED = 'suspended';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'suspended_until' => 'datetime',
            'last_login_at' => 'datetime',
            'login_code_expires_at' => 'datetime',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * يقدر يدخل دلوقتي؟ (2026-10-11 — فؤاد: الإيقاف ثابت لحد ما الأدمن يشغّله، والحظر اتشال)
     * الموقوف مابيدخلش خالص لحد «تفعيل». BLOCKED فاضل كقيمة قديمة بس وبيتعامل زي الموقوف.
     */
    public function canLogIn(): bool
    {
        return ! in_array($this->status, [self::SUSPENDED, self::BLOCKED], true);
    }

    /** سبب المنع بالعربي (لصفحة الدخول) أو null */
    public function blockedReason(): ?string
    {
        return $this->canLogIn() ? null : 'الحساب ده موقوف. تواصل مع الأدمن.';
    }
}
