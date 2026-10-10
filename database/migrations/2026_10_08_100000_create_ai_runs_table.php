<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// طلب ذكاء اصطناعي واحد وهو شغال (2026-10-08) — المتصفح بيبدأ الطلب، يستقبل الرد كلمة بكلمة
// (من Ollama مباشرة أو عن طريق السيرفر)، وبعدين يرجّعه هنا عشان يتطبّق. السطر ده بيحفظ اللي
// السيرفر جهّزه (البرومبت + السياق) بين الخطوتين، وبيمنع إن نفس الرد يتطبّق مرتين.
// مؤقت: بيتمسح بعد يوم (AiRunController::start). مش جزء من DataTransferService عمداً.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kind', 32);
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->string('task', 32);
            $table->json('context_json')->nullable();
            $table->longText('body_json');
            $table->unsignedInteger('prompt_chars')->default(0);
            $table->json('result_json')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
