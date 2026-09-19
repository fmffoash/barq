<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// تخصيص لون/خط خانة واحدة بس (بدل الخط/الألوان العامة بتاعة الموقع كله) — Phase 8.
// شكل الـ JSON: {"hero_title": {"color": "#ff0000", "font": "tajawal"}, ...} مفتاحه هو
// TemplateSlot.key بالظبط، وأي مفتاح فيه null بيرجع للقيمة العامة (الافتراضية) من غير كتابة.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->json('style_overrides_json')->nullable()->after('content_json');
        });
    }

    public function down(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn('style_overrides_json');
        });
    }
};
