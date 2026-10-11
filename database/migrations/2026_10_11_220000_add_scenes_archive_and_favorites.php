<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// (2026-10-11) فؤاد: رسمة 3D على أي قالب (ميكس) + أرشيف ومفضلة وحذف للقوالب.
// - generated_sites.scene_override: مفتاح رسمة من SceneLibrary للموقع ده بس.
// - templates.archived_at: القالب متأرشف (is_active=false كمان، والمكتبة مابترجّعوش).
// - template_favorites: مفضلة كل يوزر لوحده.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->string('scene_override', 40)->nullable()->after('font_size_scale_override');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_active');
        });

        Schema::create('template_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_favorites');
        Schema::table('templates', fn (Blueprint $table) => $table->dropColumn('archived_at'));
        Schema::table('generated_sites', fn (Blueprint $table) => $table->dropColumn('scene_override'));
    }
};
