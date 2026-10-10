<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// خط العناوين لكل نسخة قالب (2026-10-10) — مفتاح من TemplateVariant::FONTS، منفصل عن "font"
// (خط المتن). null = العناوين بنفس خط المتن (زي قبل كده بالظبط). مكتبة القوالب بتحطه لكل قالب
// (SeedTemplateLibrary::LAYOUT_FONTS)، والرندر بيقراه بـ`$variant->heading_font ?? null`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_variants', function (Blueprint $table) {
            $table->string('heading_font')->nullable()->after('font');
        });
    }

    public function down(): void
    {
        Schema::table('template_variants', function (Blueprint $table) {
            $table->dropColumn('heading_font');
        });
    }
};
