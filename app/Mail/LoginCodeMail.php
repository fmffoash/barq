<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// كود دخول الأدمن (2026-10-11) — من غير أي اسم للمشروع في الإيميل (عنوان عام «لوحة التحكم»).
class LoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'كود الدخول للوحة التحكم: '.$this->code);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.login-code');
    }
}
