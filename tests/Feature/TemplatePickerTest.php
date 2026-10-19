<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Template;
use App\Services\TemplatePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// اختيار القالب جوّه الفئة (2026-10-08): التخصص الأول (دكتور أسنان ← "عيادة أسنان" مش "عيادة
// نفسية")، وبعدين الطابع، ومن غير ما نفس القالب يتكرر في المشاريع ورا بعض.
class TemplatePickerTest extends TestCase
{
    use RefreshDatabase;

    private function medical(): array
    {
        $names = [
            'عيادة — ثقة وأمان' => 'classic', 'عيادة أسنان — ابتسامة واثقة' => 'gallery', 'عيادة نفسية — دعم بهدوء' => 'minimal',
            'عيادة أطفال — أجواء مريحة' => 'stack', 'مركز طبي فاخر — راحة تامة' => 'signature', 'مركز طبي — رعاية شاملة' => 'bento',
            'عيادة جلدية — عناية متخصصة' => 'split', 'مستشفى صغير — رعاية متكاملة' => 'glass',
        ];

        return collect($names)->map(fn ($layout, $name) => Template::factory()->create([
            'kind' => 'landing', 'category' => 'عيادات وخدمات طبية', 'name' => $name, 'layout' => $layout, 'is_active' => true,
        ]))->all();
    }

    private function picker(): TemplatePicker
    {
        return new TemplatePicker(jitter: false);
    }

    public function test_the_specialty_in_the_text_or_the_google_kind_picks_the_matching_template(): void
    {
        $t = $this->medical();

        $this->assertSame($t['عيادة أسنان — ابتسامة واثقة']->id, $this->picker()->pick('عيادات وخدمات طبية', "عيادة دكتور احمد\nطبيب أسنان")['template']->id);
        $this->assertSame($t['عيادة أسنان — ابتسامة واثقة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'Dr. Ahmed Clinic · Dentist')['template']->id);
        $this->assertSame($t['عيادة أسنان — ابتسامة واثقة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'دكتور بيعمل تقويم وتبييض')['template']->id);
        $this->assertSame($t['عيادة أطفال — أجواء مريحة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'دكتورة أطفال في مدينة نصر')['template']->id);
        $this->assertSame($t['مستشفى صغير — رعاية متكاملة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'مستشفى السلام التخصصي')['template']->id);

        $pick = $this->picker()->pick('عيادات وخدمات طبية', 'طبيب أسنان');
        $this->assertSame('مخصوص لـ«عيادة أسنان»', $pick['reason']);
    }

    // الدفعة 2 (2026-10-11): التخصصات الجديدة في المكتبة الحقيقية بتتختار من كلام فؤاد أو نوع النشاط
    // على جوجل — والبرجر مبقاش بيروح لقالب البيتزا.
    public function test_second_wave_specialties_are_picked_from_the_real_library(): void
    {
        $this->artisan('barq:seed-template-library')->assertSuccessful();
        // التست ده بيقيس مطابقة التخصص في المكتبة العادية — أولوية 3D (2026-10-19) متقفولة هنا،
        // ومتجرّبة في TemplateStatsTest.
        Setting::put(Template::PREFER_3D_SETTING, false);

        $cases = [
            ['عيادات وخدمات طبية', 'Dr. Samy · Orthopedic Surgeon', 'عيادة عظام'],
            ['عيادات وخدمات طبية', 'صيدلية الشفاء', 'صيدلية'],
            ['عيادات وخدمات طبية', 'مركز ليزك لتصحيح النظر', 'مركز ليزك'],
            ['مطاعم وكافيهات', 'مطعم برجر في المعادي', 'مطعم برجر'],
            ['مطاعم وكافيهات', 'Burger restaurant', 'مطعم برجر'],
            ['مطاعم وكافيهات', 'Pizza place', 'بيتزا وفاست فود'],
            ['مطاعم وكافيهات', 'مطعم شاورما ومناقيش', 'مطعم شامي'],
            ['جيم ولياقة بدنية', 'أكاديمية كاراتيه للأطفال', 'أكاديمية كاراتيه'],
            ['مخابز وحلويات', 'Donuts shop', 'محل دونتس'],
            ['عطور ومستحضرات تجميل', 'محل بخور ودهن عود', 'محل بخور وعود'],
        ];

        foreach ($cases as [$category, $context, $specialty]) {
            $picked = $this->picker()->pick($category, $context)['template'];
            $this->assertSame($specialty, trim(explode('—', $picked->name)[0]), $context);
        }
    }

    public function test_the_style_decides_when_the_specialty_does_not(): void
    {
        $t = $this->medical();

        $this->assertSame($t['مركز طبي فاخر — راحة تامة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'مركز طبي', 'فاخر')['template']->id);
        $this->assertSame($t['عيادة نفسية — دعم بهدوء']->id, $this->picker()->pick('عيادات وخدمات طبية', 'عيادة', 'هادي وبسيط')['template']->id);
    }

    public function test_a_template_used_by_recent_projects_gives_way_to_another_one(): void
    {
        $t = $this->medical();
        $generic = $this->picker()->pick('عيادات وخدمات طبية', 'عيادة')['template'];

        Project::factory()->count(2)->create(['template_id' => $generic->id]);

        $this->assertNotSame($generic->id, $this->picker()->pick('عيادات وخدمات طبية', 'عيادة')['template']->id);
        // التخصص الواضح بيكسب حتى لو القالب اتستخدم قبل كده.
        Project::factory()->count(2)->create(['template_id' => $t['عيادة أسنان — ابتسامة واثقة']->id]);
        $this->assertSame($t['عيادة أسنان — ابتسامة واثقة']->id, $this->picker()->pick('عيادات وخدمات طبية', 'طبيب أسنان')['template']->id);
    }

    public function test_changing_the_template_never_returns_the_same_one_and_prefers_another_layout(): void
    {
        $t = $this->medical();
        $current = $t['عيادة أسنان — ابتسامة واثقة'];

        $pick = $this->picker()->pick('عيادات وخدمات طبية', 'طبيب أسنان', 'فاخر', exceptId: $current->id, avoidLayout: $current->layout);

        $this->assertNotSame($current->id, $pick['template']->id);
        $this->assertSame($t['مركز طبي فاخر — راحة تامة']->id, $pick['template']->id);
    }

    public function test_style_words_are_understood_in_egyptian_arabic(): void
    {
        $this->assertSame(['فاخر'], $this->picker()->styleKeys('عايز قالب أفخم شوية'));
        $this->assertSame(['بسيط'], $this->picker()->styleKeys('خليه أهدى'));
        $this->assertSame([], $this->picker()->styleKeys('غيّر القالب'));
    }
}
