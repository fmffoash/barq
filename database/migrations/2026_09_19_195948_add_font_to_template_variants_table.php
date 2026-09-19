<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// بيضيف عمود اختيار الخط لكل نسخة قالب — الخط بيتطبّق على الموقع المنشور كله (خط عام)،
// ومنفصل تماماً عن أي تخصيص خط لخانة واحدة بس (generated_sites.style_overrides_json، Phase 8).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_variants', function (Blueprint $table) {
            $table->string('font')->default('cairo')->after('colors_json');
        });
    }

    public function down(): void
    {
        Schema::table('template_variants', function (Blueprint $table) {
            $table->dropColumn('font');
        });
    }
};
