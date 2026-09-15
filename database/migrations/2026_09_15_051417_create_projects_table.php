<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// المشروع — العميل الواحد (أو الطلب الواحد) اللي بنبني له موقع. المشروع بيختار قالب ونسخة
// معيّنة، وبيتولّد منه موقع واحد فعلي (generated_sites) بمجرد ما يتملى بالبيانات.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained();
            $table->foreignId('template_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // اسم المشروع أو اسم العميل — للتعريف الداخلي بس
            $table->string('slug')->nullable(); // أساس رابط المعاينة (subdomain) — لو فاضي بيتولّد من الاسم
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            // حالة المشروع: مسودة (لسه بيتملى) / اتولّد موقعه / اتسلّم للعميل فعلاً.
            $table->enum('status', ['draft', 'generated', 'delivered'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
