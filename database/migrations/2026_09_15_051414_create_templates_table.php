<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// جدول القوالب — كل صف هنا قالب موقع جاهز (تصميمه ثابت) ممكن يتم تعبئته ببيانات عميل مختلف
// أكتر من مرة. القالب الواحد ممكن يكون له أكتر من "نسخة" (ألوان/أقسام مختلفة) في جدول
// template_variants، وأكتر من "خانة محتوى" قابلة للتعبئة في جدول template_slots.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم القالب زي ما هيظهر في لوحة التحكم
            $table->string('slug')->unique(); // معرّف نصي فريد للقالب (يُستخدم داخلياً)
            $table->string('category')->nullable(); // تصنيف اختياري (مطعم، عيادة، متجر...)
            // نوع القالب: صفحة هبوط بسيطة (landing) أو موقع ووردبريس (wordpress) — المرحلة
            // الحالية بتدعم landing بس، وووردبريس متوقّعة في مرحلة لاحقة من الخطة.
            $table->enum('kind', ['landing', 'wordpress'])->default('landing');
            $table->text('license_note')->nullable(); // ملاحظة ترخيص/مصدر التصميم لو موجودة
            $table->boolean('is_active')->default(true); // القوالب غير النشطة بتختفي من صفحة "مشروع جديد"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
