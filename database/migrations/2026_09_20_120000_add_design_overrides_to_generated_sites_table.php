<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// تخصيص شكل الموقع الناتج بالكامل (ألوان/خط/ترتيب أقسام) لمشروع واحد بس — مستقل عن
// TemplateVariant المشترك بين كل المشاريع اللي بتستخدم نفس القالب. كل عمود null افتراضياً
// (يعني "ورّث من النسخة")، وبيتملى بس لما الأدمن يفعّل تخصيص الموقع ده تحديداً من صفحة
// تعبئة المحتوى — الفرق عن style_overrides_json (Phase 8) إن ده تخصيص خانة واحدة بس، وده
// تخصيص شكل الموقع كله (2026-09-20، رد على طلب فؤاد سهولة تعديل التصميم حتى بعد التسليم).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->json('colors_override_json')->nullable()->after('style_overrides_json');
            $table->string('font_override')->nullable()->after('colors_override_json');
            $table->json('sections_override_json')->nullable()->after('font_override');
        });
    }

    public function down(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn(['colors_override_json', 'font_override', 'sections_override_json']);
        });
    }
};
