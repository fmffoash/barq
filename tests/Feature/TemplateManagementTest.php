<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_templates(): void
    {
        $response = $this->get('/templates');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_create_a_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/templates', [
            'name' => 'قالب المطاعم',
            'category' => 'مطاعم',
            'kind' => 'landing',
            'license_note' => null,
            'is_active' => '1',
        ]);

        $template = Template::first();

        $response->assertRedirect(route('templates.show', $template));
        $this->assertSame('قالب المطاعم', $template->name);
        $this->assertSame('landing', $template->kind);
        $this->assertNotEmpty($template->slug);
        $this->assertTrue($template->is_active);
    }

    public function test_duplicate_template_names_get_a_unique_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/templates', [
            'name' => 'قالب العيادات',
            'kind' => 'landing',
        ]);

        $this->actingAs($user)->post('/templates', [
            'name' => 'قالب العيادات',
            'kind' => 'wordpress',
        ]);

        $slugs = Template::orderBy('id')->pluck('slug')->all();

        $this->assertCount(2, array_unique($slugs));
    }

    public function test_template_creation_requires_valid_kind(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/templates', [
            'name' => 'قالب غير صالح',
            'kind' => 'something-else',
        ]);

        $response->assertSessionHasErrors('kind');
        $this->assertDatabaseCount('templates', 0);
    }

    public function test_admin_can_view_the_templates_index_and_show_pages(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['name' => 'قالب متاجر']);

        $index = $this->actingAs($user)->get('/templates');
        $index->assertOk();
        $index->assertSee('قالب متاجر');

        $show = $this->actingAs($user)->get(route('templates.show', $template));
        $show->assertOk();
        $show->assertSee('قالب متاجر');
    }

    public function test_admin_can_update_a_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['name' => 'اسم قديم', 'is_active' => true]);

        $response = $this->actingAs($user)->put(route('templates.update', $template), [
            'name' => 'اسم جديد',
            'kind' => $template->kind,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('templates.show', $template));
        $template->refresh();
        $this->assertSame('اسم جديد', $template->name);
        $this->assertFalse($template->is_active);
    }

    public function test_admin_can_delete_a_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $response = $this->actingAs($user)->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    public function test_admin_can_add_a_variant_with_json_fields_that_round_trip_correctly(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $response = $this->actingAs($user)->post(route('templates.variants.store', $template), [
            'name' => 'الأساسية',
            'colors_json' => json_encode(['primary' => '#f59e0b']),
            'sections_json' => json_encode(['hero', 'services']),
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('templates.show', $template));

        $variant = $template->variants()->first();
        $this->assertSame('الأساسية', $variant->name);
        $this->assertSame(['primary' => '#f59e0b'], $variant->colors_json);
        $this->assertSame(['hero', 'services'], $variant->sections_json);
        $this->assertTrue($variant->is_default);
    }

    public function test_only_one_variant_can_be_default_per_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $first = TemplateVariant::factory()->for($template)->create(['is_default' => true]);
        $second = TemplateVariant::factory()->for($template)->create(['is_default' => false]);

        $this->actingAs($user)->put(route('template-variants.update', $second), [
            'name' => $second->name,
            'is_default' => '1',
        ]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_admin_can_delete_a_variant(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $variant = TemplateVariant::factory()->for($template)->create();

        $response = $this->actingAs($user)->delete(route('template-variants.destroy', $variant));

        $response->assertRedirect(route('templates.show', $template));
        $this->assertDatabaseMissing('template_variants', ['id' => $variant->id]);
    }

    public function test_admin_can_add_a_content_slot(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $response = $this->actingAs($user)->post(route('templates.slots.store', $template), [
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان الصفحة الرئيسية',
            'label_en' => 'Hero title',
            'slot_type' => 'text',
            'is_required' => '1',
            'sort_order' => '1',
        ]);

        $response->assertRedirect(route('templates.show', $template));

        $slot = $template->slots()->first();
        $this->assertSame('hero', $slot->section_key);
        $this->assertSame('hero_title', $slot->key);
        $this->assertTrue($slot->is_required);
        $this->assertSame(1, $slot->sort_order);
    }

    public function test_slot_keys_must_be_unique_within_the_same_template(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $response = $this->actingAs($user)->post(route('templates.slots.store', $template), [
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان تاني',
            'slot_type' => 'text',
        ]);

        $response->assertSessionHasErrors('key');
        $this->assertSame(1, $template->slots()->count());
    }

    public function test_the_same_slot_key_is_allowed_across_different_templates(): void
    {
        $user = User::factory()->create();
        $templateA = Template::factory()->create();
        $templateB = Template::factory()->create();

        $templateA->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $response = $this->actingAs($user)->post(route('templates.slots.store', $templateB), [
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان تاني',
            'slot_type' => 'text',
        ]);

        $response->assertSessionDoesntHaveErrors('key');
        $this->assertSame(1, $templateB->slots()->count());
    }

    public function test_updating_a_slot_without_changing_its_key_does_not_trigger_a_uniqueness_error(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $slot = $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $response = $this->actingAs($user)->put(route('template-slots.update', $slot), [
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان معدّل',
            'slot_type' => 'textarea',
            'is_required' => '1',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $slot->refresh();
        $this->assertSame('عنوان معدّل', $slot->label_ar);
        $this->assertSame('textarea', $slot->slot_type);
        $this->assertTrue($slot->is_required);
    }

    public function test_admin_can_delete_a_slot(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $slot = $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $response = $this->actingAs($user)->delete(route('template-slots.destroy', $slot));

        $response->assertRedirect(route('templates.show', $template));
        $this->assertDatabaseMissing('template_slots', ['id' => $slot->id]);
    }

    public function test_deleting_a_template_cascades_to_its_variants_and_slots(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        $variant = TemplateVariant::factory()->for($template)->create();
        $slot = $template->slots()->create([
            'section_key' => 'hero',
            'key' => 'hero_title',
            'label_ar' => 'عنوان',
            'slot_type' => 'text',
        ]);

        $this->actingAs($user)->delete(route('templates.destroy', $template));

        $this->assertDatabaseMissing('template_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('template_slots', ['id' => $slot->id]);
    }
}
