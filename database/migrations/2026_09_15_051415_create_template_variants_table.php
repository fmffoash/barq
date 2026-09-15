<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// نسخ القالب — القالب الواحد ممكن يكون له أكتر من نسخة بألوان وأقسام مختلفة (مثلاً
// "الأساسية" و"الموسّعة")، وكل مشروع بيختار نسخة معيّنة وقت إنشائه.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // اسم النسخة زي ما هيظهر للمستخدم
            $table->string('slug'); // معرّف نصي للنسخة (فريد داخل نفس القالب بس)
            $table->json('colors_json')->nullable(); // مجموعة الألوان الخاصة بالنسخة دي
            $table->json('sections_json')->nullable(); // الأقسام المتضمّنة في النسخة دي وترتيبها
            $table->boolean('is_default')->default(false); // النسخة المختارة تلقائياً عند اختيار القالب
            $table->timestamps();

            $table->unique(['template_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_variants');
    }
};
