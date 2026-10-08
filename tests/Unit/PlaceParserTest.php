<?php

namespace Tests\Unit;

use App\Support\PastedText;
use App\Support\PlaceParser;
use PHPUnit\Framework\TestCase;

// بيانات المكان من كوبي جوجل مابس بالحرف (2026-10-08) — التليفون والمواعيد والعنوان بيتحطوا في
// الموقع مباشرة، فلازم يطلعوا صح من غير ذكاء اصطناعي.
class PlaceParserTest extends TestCase
{
    private const ARABIC_PASTE = <<<'TXT'
عيادة دكتور احمد انور الهلالي
٤٫٩
(١٢٧)
طبيب أسنان
نظرة عامة
آراء
لمحة
الاتجاهات
حفظ
الأماكن المجاورة
إرسال إلى هاتفك
مشاركة
شارع الجمهورية، المنصورة (قسم 2)، الدقهلية 35511
مفتوح ⋅ يغلق في 10 م
010 1234 5678
8GQ2+3V المنصورة (قسم 2)
drahmed-clinic.com
السعر بيبدأ من 19500 جنيه
TXT;

    private const ENGLISH_PASTE = <<<'TXT'
Bella Pizza
4.6(2,315)
Pizza restaurant · $$
Overview
Menu
Reviews
About
Directions
Save
Share
12 Abbas El Akkad St, Nasr City, Cairo Governorate 11765
Open 24 hours
+20 2 22609988
https://maps.app.goo.gl/AbCdEf123
TXT;

    public function test_an_arabic_google_maps_paste_gives_exact_contact_data(): void
    {
        $place = PlaceParser::parse(PastedText::clean(self::ARABIC_PASTE));

        $this->assertSame('عيادة دكتور احمد انور الهلالي', $place['name']);
        $this->assertSame(4.9, $place['rating']);
        $this->assertSame(127, $place['reviews']);
        $this->assertSame('طبيب أسنان', $place['kind']);
        $this->assertSame(['01012345678'], $place['phones'], 'a price like 19500 is never a phone');
        $this->assertSame('شارع الجمهورية، المنصورة (قسم 2)، الدقهلية 35511', $place['address']);
        $this->assertSame(['مفتوح — يغلق في 10 م'], $place['hours']);
        $this->assertSame('8GQ2+3V', $place['plus_code']);
        $this->assertSame('https://drahmed-clinic.com', $place['website']);

        $this->assertSame('https://wa.me/201012345678', PlaceParser::contactLink($place));
        $this->assertSame('مفتوح — يغلق في 10 م — شارع الجمهورية، المنصورة (قسم 2)، الدقهلية 35511', PlaceParser::contactNote($place));
        $this->assertSame('تقييمنا 4.9 ★ على جوجل من 127 تقييم', PlaceParser::ratingLine($place));
    }

    public function test_an_english_paste_with_a_landline_and_a_maps_link(): void
    {
        $place = PlaceParser::parse(PastedText::clean(self::ENGLISH_PASTE));

        $this->assertSame('Bella Pizza', $place['name']);
        $this->assertSame(4.6, $place['rating']);
        $this->assertSame(2315, $place['reviews']);
        $this->assertSame('Pizza restaurant', $place['kind']);
        $this->assertSame(['0222609988'], $place['phones']);
        $this->assertSame('12 Abbas El Akkad St, Nasr City, Cairo Governorate 11765', $place['address']);
        $this->assertSame(['Open 24 hours'], $place['hours']);
        $this->assertSame('https://maps.app.goo.gl/AbCdEf123', $place['maps_url']);
        $this->assertArrayNotHasKey('website', $place, 'a google link is not the business website');

        $this->assertSame('tel:0222609988', PlaceParser::contactLink($place));
    }

    public function test_a_normal_description_is_not_mistaken_for_a_place_name(): void
    {
        $place = PlaceParser::parse('عيادة أسنان في المنصورة اسمها د. أحمد، بنعمل تقويم وتبييض، التليفون 01012345678');

        $this->assertArrayNotHasKey('name', $place);
        $this->assertSame(['01012345678'], $place['phones']);
        $this->assertNull(PlaceParser::ratingLine($place));
    }

    public function test_the_prompt_block_lists_only_what_was_found(): void
    {
        $block = PlaceParser::promptBlock(['name' => 'Bella Pizza', 'phones' => ['0222609988'], 'rating' => 4.6, 'reviews' => 2315]);

        $this->assertStringContainsString('- الاسم: Bella Pizza', $block);
        $this->assertStringContainsString('- التليفون: 0222609988', $block);
        $this->assertStringContainsString('4.6 ★', $block);
        $this->assertStringNotContainsString('العنوان', $block);
        $this->assertSame('', PlaceParser::promptBlock([]));
    }

    public function test_hotlines_count_only_on_their_own_line(): void
    {
        $this->assertSame(['19019'], PlaceParser::phones("كلمنا\nالخط الساخن: 19019"));
        $this->assertSame([], PlaceParser::phones('الباقة بـ 16500 جنيه بس'));
        $this->assertSame(['0501234567'], PlaceParser::phones('050 123 4567'));
        $this->assertSame(['+966501234567'], PlaceParser::phones('+966 50 123 4567'));
    }
}
