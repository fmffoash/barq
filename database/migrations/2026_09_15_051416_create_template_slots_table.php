<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// خانات المحتوى القابلة للتعبئة في كل قالب — كل صف هنا يمثّل حقل واحد (عنوان، وصف، صورة...)
// جوه قسم معيّن من الموقع (hero، services، about...). القيم الفعلية اللي العميل بيدخلها
// بتتخزن في generated_sites.content_json مفتاحها هو عمود "key" هنا.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('section_key'); // اسم القسم اللي الخانة دي تابعة له (hero, services, about...)
            $table->string('key'); // مفتاح الخانة الفريد داخل القالب (hero_title, service_1_desc...)
            $table->string('label_ar'); // التسمية اللي هتظهر في فورم إنشاء المشروع بالعربي
            $table->string('label_en')->nullable(); // نفس التسمية بالإنجليزي (اختياري)
            // نوع الخانة: نص قصير، نص طويل، صورة، قايمة، أو رابط — بيحدد شكل حقل الإدخال في الفورم.
            $table->enum('slot_type', ['text', 'textarea', 'image', 'list', 'link'])->default('text');
            $table->boolean('is_required')->default(false); // لازم تتملى قبل ما المشروع يتحفظ؟
            $table->unsignedInteger('sort_order')->default(0); // ترتيب ظهور الخانة داخل قسمها
            $table->timestamps();

            $table->unique(['template_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_slots');
    }
};
