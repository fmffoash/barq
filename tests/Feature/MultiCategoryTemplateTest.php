<?php

namespace Tests\Feature;

use App\Console\Commands\SeedTemplateLibrary;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Services\TemplatePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// (2026-10-11) فؤاد: «لو القالب ينفع في فئتين خليه يظهر في الفئتين» — زي «كافيه حلويات» في مخابز ومطاعم.
class MultiCategoryTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_template_shows_in_its_extra_category(): void
    {
        $dessert = Template::factory()->create(['kind' => 'landing', 'category' => 'مخابز وحلويات', 'extra_categories' => ['مطاعم وكافيهات'], 'name' => 'كافيه حلويات — ركن دافي', 'is_active' => true]);
        TemplateVariant::factory()->for($dessert)->create(['is_default' => true]);
        $bakery = Template::factory()->create(['kind' => 'landing', 'category' => 'مخابز وحلويات', 'name' => 'مخبز — عيش طازة', 'is_active' => true]);

        $inCafes = Template::inCategory('مطاعم وكافيهات')->pluck('id')->all();
        $this->assertSame([$dessert->id], $inCafes);
        $this->assertEqualsCanonicalizing([$dessert->id, $bakery->id], Template::inCategory('مخابز وحلويات')->pluck('id')->all());
        $this->assertTrue($dessert->belongsToCategory('مطاعم وكافيهات'));
        $this->assertFalse($bakery->belongsToCategory('مطاعم وكافيهات'));

        // الاختيار الذكي في فئة المطاعم بيلاقي قالب الحلويات
        $this->assertSame($dessert->id, (new TemplatePicker(false))->pick('مطاعم وكافيهات', 'كافيه حلويات')['template']->id);
    }

    public function test_library_rules_give_extra_categories(): void
    {
        $this->assertSame(['مطاعم وكافيهات'], SeedTemplateLibrary::extraCategoriesFor('كافيه حلويات — ركن دافي', 'مخابز وحلويات'));
        $this->assertEqualsCanonicalizing(['صالونات وتجميل', 'متاجر إلكترونية'], SeedTemplateLibrary::extraCategoriesFor('متجر مكياج — x', 'عطور ومستحضرات تجميل'));
        $this->assertNull(SeedTemplateLibrary::extraCategoriesFor('متجر — بسيط', 'متاجر إلكترونية'));
        $this->assertNull(SeedTemplateLibrary::extraCategoriesFor('مطعم — واجهة دافئة', 'مطاعم وكافيهات'));
    }
}
