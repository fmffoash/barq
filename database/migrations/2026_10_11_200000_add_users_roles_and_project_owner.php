<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يوزرات متعددة (2026-10-11، طلب فؤاد): أدمن + يوزرات عادية، كل يوزر يشوف مشاريعه بس.
 * - users: الدور، والحالة (شغال/محظور/موقوف لحد تاريخ)، وآخر دخول، وكود الدخول بالإيميل للأدمن (متشفّر).
 * - صاحب المشروع = العمود الموجود projects.created_by (كان فاضي دايماً) — القديم بيتنسب للأدمن الموجود.
 * - ai_runs.user_id: صاحب طلب الذكاء الاصطناعي (مايقدرش يوصله غيره).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('status', 16)->default('active')->after('is_admin'); // active | blocked | suspended
            $table->timestamp('suspended_until')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('suspended_until');
            $table->string('login_code_hash')->nullable()->after('last_login_at');
            $table->timestamp('login_code_expires_at')->nullable()->after('login_code_hash');
            $table->unsignedTinyInteger('login_code_attempts')->default(0)->after('login_code_expires_at');
        });

        Schema::table('ai_runs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('project_id')->constrained()->cascadeOnDelete();
        });

        // قبل كده كان فيه يوزر واحد بس = الأدمن
        DB::table('users')->update(['is_admin' => true]);
        if ($adminId = DB::table('users')->orderBy('id')->value('id')) {
            DB::table('projects')->whereNull('created_by')->update(['created_by' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('ai_runs', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'status', 'suspended_until', 'last_login_at', 'login_code_hash', 'login_code_expires_at', 'login_code_attempts']);
        });
    }
};
