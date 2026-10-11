<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// (2026-10-11) القالب اللي ينفع لفئتين يظهر في الاتنين — زي «كافيه حلويات» (مخابز + مطاعم)
// أو «متجر عطور» (عطور + متاجر). الفئة الأساسية زي ما هي، والإضافية قايمة JSON.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('extra_categories')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('extra_categories');
        });
    }
};
