<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// السيدر ده متعمّد يفضل فاضي — حساب الأدمن الوحيد في النظام بيتعمل بالأمر التفاعلي:
// php artisan barq:create-admin (بيسأل عن الباسورد وقت التشغيل وميكتبهاش في أي ملف أو لوج).
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        //
    }
}
