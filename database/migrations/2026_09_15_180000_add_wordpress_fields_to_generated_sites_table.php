<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// أعمدة موقع الـ WordPress — Phase 5. بتتملى بس لو القالب من نوع "ووردبريس" وبعد ما WordPressService
// يعمل site فعلي على الشبكة. لحد ما يحصل ده كلهم بيفضلوا null، وده اللي بنستخدمه (wp_site_id !== null)
// عشان نعرف إن الموقع اتجهّز فعلاً على WordPress ولا لسه.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            // الـ ID بتاع الـ site جوّه شبكة الـ Multisite نفسها (اللي wp_insert_site() بترجّعه).
            $table->unsignedBigInteger('wp_site_id')->nullable()->after('status');
            // رابط الموقع الفعلي على شبكة الـ WordPress (مش رابط المعاينة بتاع برق).
            $table->string('wp_site_url')->nullable()->after('wp_site_id');
            // رابط لوحة تحكم الموقع ده (wp-admin) — بيتحط في زرار "افتح لوحة التحكم" في واجهة برق.
            $table->string('wp_admin_url')->nullable()->after('wp_site_url');
            // امتى اتعمل الـ site فعلياً على الشبكة (مختلف عن exported_at/last_generated_at اللي
            // بتاعة التصدير الثابت والتعديل اليدوي).
            $table->timestamp('wp_provisioned_at')->nullable()->after('wp_admin_url');
        });
    }

    public function down(): void
    {
        Schema::table('generated_sites', function (Blueprint $table) {
            $table->dropColumn(['wp_site_id', 'wp_site_url', 'wp_admin_url', 'wp_provisioned_at']);
        });
    }
};
