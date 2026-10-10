<?php

namespace App\Services\Ai\Runs;

use App\Models\AiRun;
use App\Services\Ai\AiResult;
use Illuminate\Http\Request;

/**
 * نوع واحد من طلبات الذكاء الاصطناعي اللي المتصفح بيشغّلها بالعدّاد (AiRunController) —
 * إنشاء مشروع، رسالة على مشروع، اقتراح محتوى الخانات الفاضية. كل نوع بيعرف يجهّز طلبه
 * ويطبّق رده، والكنترولر ملوش دعوة بالتفاصيل.
 */
interface RunHandler
{
    /**
     * بيتحقق من الإدخال ويجهّز الطلب. يا إما طلب جاهز للذكاء الاصطناعي (ai + context)، يا إما
     * نتيجة فورية من غير ذكاء اصطناعي خالص (reply + redirect): خطأ في الإدخال، أو طلب اتنفّذ
     * على طول (زي "غيّر الخط لكايرو").
     *
     * @return array{ok: bool, reply?: string, redirect?: string|null, project_id?: int|null, context?: array<string, mixed>, ai?: array{prompt: string, schema: array<string, mixed>|null, task: string}}
     */
    public function prepare(Request $request): array;

    /**
     * بيطبّق رد الذكاء الاصطناعي (أو فشله — كل نوع عنده تصرّف احتياطي لما الرد ميجيش).
     *
     * reload = الصفحة تتحدّث بعد ما فؤاد يقفل الرسالة حتى لو فشل (الرد اتسجّل في الشات مثلاً).
     *
     * @return array{ok: bool, reply: string, redirect: string|null, reload?: bool}
     */
    public function complete(AiRun $run, AiResult $result): array;
}
