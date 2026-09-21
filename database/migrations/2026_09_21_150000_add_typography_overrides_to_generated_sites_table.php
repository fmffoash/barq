<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// تكملة تخصيص الخط (Phase 16، 2026-09-21) — font_override (Phase 8) بيغيّر عائلة الخط
// (اسمه) بس، فؤاد طلب كمان تحكّم في تخين الخط (weight)، مايل ولا عادي (style)، وحجم النص
// العام للصفحة كلها (size scale). التلاتة null افتراضياً (يعني "زي ما القالب معمول"، نفس
// نمط بواقي design overrides). font_size_scale رقم عشري (0.85–1.3 مثلاً) بيتطبّق كـ
// font-size على وسم <html> نفسه — عشان كل كلاسات Tailwind الحجمية (text-sm/text-3xl/...)
// بتستخدم rem (نسبي لـ<html> مش لـ<body>)، فتغيير حجم <html> بيكبّر/يصغّر كل نصوص الموقع
// نسبياً من غير أي تعديل في أي layout من الـ16 (اكتشاف مهم وفّر إعادة كتابة كل التصميمات).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->string('font_weight_override')->nullable()->after('font_override');
            $table->string('font_style_override')->nullable()->after('font_weight_override');
            $table->decimal('font_size_scale_override', 3, 2)->nullable()->after('font_style_override');
        });
    }

    public function down(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn(['font_weight_override', 'font_style_override', 'font_size_scale_override']);
        });
    }
};
