<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// سجل محادثة "أنشئ/عدّل بالذكاء الاصطناعي" (Phase 10) — رسالة المستخدم الحرة + رد المساعد
// النصي، مرتبطين بمشروع واحد. الأفعال الفعلية (تغيير قالب/محتوى/لون/خط) بتتنفذ وبتتخزن في
// جداول المشروع نفسها (projects/generated_sites) زي أي تعديل عادي — هنا بس سجل المحادثة
// نفسها عشان السياق يفضل موجود لو المستخدم كمّل يتكلم في نفس المشروع.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
