<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// الموقع الناتج فعلياً — ده اللي middleware اكتشاف الموقع (DetectSite) بيدوّر عليه بالـ slug
// بتاعه عشان يعرف يعرض أنهي محتوى على أنهي subdomain. كل مشروع (project) بيولّد منه موقع واحد
// بيتحدّث بدل ما يتكرر لو العميل طلب تعديل.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // الجزء اللي هيظهر في الرابط: {slug}.{base_domain} — لازم يكون فريد على مستوى المنصة كلها.
            $table->string('slug')->unique();
            // القيم الفعلية لكل خانات القالب (مفتاح كل قيمة = key بتاع template_slots المطابق).
            $table->json('content_json')->nullable();
            // مسودة لسه بتتظبط / منشورة وشغالة كمعاينة / اتأرشفت (العميل مش محتاجها أونلاين تاني).
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('exported_at')->nullable(); // آخر مرة اتصدّر كملفات ثابتة (مرحلة قادمة)
            $table->timestamp('last_generated_at')->nullable(); // آخر مرة اتحدّث المحتوى فعلياً
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_sites');
    }
};
