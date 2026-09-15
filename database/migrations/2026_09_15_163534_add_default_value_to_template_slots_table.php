<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('template_slots', function (Blueprint $table) {
            // محتوى افتراضي جاهز للخانة دي — نفس فكرة content_json بتاع GeneratedSite (نص
            // بسيط لخانات text/textarea/link/image، أو array لخانات list) بس مخزّن مع القالب
            // نفسه عشان أي مشروع جديد من القالب ده ينولد بمحتوى مبدئي بدل ما يبدأ فاضي.
            $table->json('default_value')->nullable()->after('is_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_slots', function (Blueprint $table) {
            $table->dropColumn('default_value');
        });
    }
};
