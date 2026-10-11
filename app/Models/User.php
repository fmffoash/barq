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

    /** يقدر يدخل دلوقتي؟ (الإيقاف المؤقت بيخلص لوحده بعد تاريخه) */
    public function canLogIn(): bool
    {
        return match ($this->status) {
            self::BLOCKED => false,
            self::SUSPENDED => $this->suspended_until !== null && $this->suspended_until->isPast(),
            default => true,
        };
    }

    /** سبب المنع بالعربي (لصفحة الدخول) أو null */
    public function blockedReason(): ?string
    {
        if ($this->canLogIn()) {
            return null;
        }

        return $this->status === self::BLOCKED
            ? 'الحساب ده متقفل. تواصل مع الأدمن.'
            : 'الحساب ده موقوف مؤقتاً لحد '.$this->suspended_until?->timezone('Africa/Cairo')->format('Y-m-d H:i').'.';
    }
}
