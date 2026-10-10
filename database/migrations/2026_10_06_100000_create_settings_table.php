<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// إعدادات بتتغيّر من جوّه البرنامج نفسه (2026-10-06، التشغيل المحلي) — أولها نموذج الذكاء
// الاصطناعي اللي فؤاد اختاره من صفحة الإعدادات وسرعة جهازه المقاسة (للعدّاد). خاصة بالجهاز ده
// بس، فمش جزء من DataTransferService::TABLES عمداً (النموذج المتسطّب وسرعته بيختلفوا من جهاز لجهاز).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
