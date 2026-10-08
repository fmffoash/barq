<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// كوبي جوجل مابس (2026-10-08): بيانات المكان اللي اتعرفت من الكلام (PlaceParser — تليفون،
// عنوان، مواعيد، تقييم...) على المشروع، وكل الصور اللي فؤاد لزقها/رفعها مع الطلب على الموقع
// (photo_pool_json) — أول صور بتتحط في خانات الصور، والباقي متاح يختار منه بعدين.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('place_json')->nullable()->after('contact_email');
        });

        Schema::table('generated_sites', function (Blueprint $table) {
            $table->json('photo_pool_json')->nullable()->after('custom_blocks_json');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('place_json');
        });

        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn('photo_pool_json');
        });
    }
};
