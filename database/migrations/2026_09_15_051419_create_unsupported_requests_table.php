<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// سجل الطلبات اللي النموذج الذكي (مرحلة تانية) مش قادر ينفّذها ضمن الأكشنين المسموحين
// (إنشاء تصميم جديد / تعديل تصميم موجود). أي طلب غريب أو غامض بيتسجّل هنا للمراجعة اليدوية
// بدل ما النموذج يحاول يخمّن وينفّذ حاجة غلط.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unsupported_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type')->nullable(); // تصنيف حر للطلب لو اتحدد وقت المراجعة
            $table->text('prompt_text'); // النص اللي المستخدم كتبه فعلاً
            $table->text('ai_raw_response')->nullable(); // رد النموذج الخام (للمراجعة بس، مش بيتعرض لحد)
            $table->timestamp('reviewed_at')->nullable(); // اتراجع الطلب ده امتى (لو اتراجع أصلاً)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unsupported_requests');
    }
};
