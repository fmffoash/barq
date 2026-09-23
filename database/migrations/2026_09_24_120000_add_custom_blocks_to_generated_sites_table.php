<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// عناصر مضافة من فؤاد بنفسه عن طريق شات الذكاء الاصطناعي (2026-09-24، "ضيف مربع/صورة
// جديدة") — مش جزء من خانات القالب الثابتة (template_slots)، فبتتخزن هنا منفصلة تماماً.
// كل عنصر: {key, type: text|image, label, content} — كل واحد بيترندر كـsection جديد كامل
// في آخر الصفحة (SiteRenderer::render())، قابل لإعادة الترتيب/الترتيب الحر زي أي section
// تاني بالظبط. null افتراضياً = مفيش عناصر مضافة (السلوك الحالي، صفر تأثير على أي مشروع
// قديم).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->json('custom_blocks_json')->nullable()->after('sections_override_json');
        });
    }

    public function down(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn('custom_blocks_json');
        });
    }
};
