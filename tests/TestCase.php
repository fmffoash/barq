<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setting بيقرا الجدول مرة واحدة في الطلب — بين تست والتاني الداتابيز بتتمسح.
        Setting::flushCache();

        // صفر نداء شبكة حقيقي من التستات (Ollama/WordPress/تنزيل صور) — أي نداء لازم يكون
        // متزيّف صراحةً بـHttp::fake، وإلا التست يفشل بدل ما يكلّم الجهاز بجد.
        Http::preventStrayRequests();
    }
}
