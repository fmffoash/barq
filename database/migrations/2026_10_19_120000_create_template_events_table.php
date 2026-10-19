<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// (2026-10-19) سجل اختيار/تبديل القوالب — لإحصائيات القوالب في لوحة الأدمن (الأكتر استخدام، واللي
// اتستخدم وبعدين اتبدّل). سطر لكل مرة مشروع خد قالب: from فاضي = أول قالب للمشروع، غير كده تبديل.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('to_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['to_template_id', 'created_at']);
            $table->index('from_template_id');
        });

        // المشاريع الموجودة قبل السجل: قالبها الحالي بيتحسب أول اختيار (التبديلات القديمة مش متسجّلة).
        DB::table('projects')->whereNotNull('template_id')->orderBy('id')->each(function ($project): void {
            DB::table('template_events')->insert([
                'project_id' => $project->id,
                'to_template_id' => $project->template_id,
                'user_id' => $project->created_by,
                'created_at' => $project->created_at,
                'updated_at' => $project->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_events');
    }
};
