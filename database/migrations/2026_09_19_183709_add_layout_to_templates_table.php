<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// بيضيف عمود التصميم البصري للقالب — كل قالب بقى بيختار "layout" (classic/modern/gallery)
// بيحدد الشكل الفعلي اللي هيترندر بيه (هيرو/نافبار/تقسيمات الأعمدة)، منفصل تماماً عن خانات
// المحتوى نفسها (template_slots) عشان الذكاء الاصطناعي ولوحة الإدارة يفضلوا شغالين زي ما هم
// من غير أي تعديل، أياً كان التصميم المختار.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('layout')->default('classic')->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
