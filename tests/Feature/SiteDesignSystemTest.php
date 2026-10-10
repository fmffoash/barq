<?php

namespace Tests\Feature;

use App\Models\GeneratedSite;
use App\Models\Project;
use App\Models\Template;
use App\Models\TemplateVariant;
use App\Models\User;
use App\Services\SiteRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// البنية المشتركة لرندر المواقع (2026-10-10): لون نص فوق اللون الأساسي، خط العناوين، قسم آراء
// العملاء وتفكيك كل رأي، تصنيف قسم التواصل، زرار واتساب العائم، والشبكات اللي بتحسب الأعمدة حسب
// العدد. أي تصميم من الـ16 بيعتمد على ده، فالاختبارات هنا على المنطق نفسه + الرندر الفعلي.
class SiteDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * قالب بنفس بنية مكتبة القوالب (6 أقسام) + صور معرض لحد 6 (عقد 1).
     */
    private function libraryLikeTemplate(string $layout, int $galleryImages = 3): Template
    {
        $template = Template::factory()->create(['kind' => 'landing', 'layout' => $layout]);
        $slots = [
            ['hero', 'hero_title', 'text', 'العنوان الرئيسي'],
            ['hero', 'hero_subtitle', 'textarea', 'الوصف المختصر'],
            ['hero', 'hero_cta', 'link', 'اطلب دلوقتي'],
            ['hero', 'hero_image', 'image', 'صورة الغلاف'],
            ['about', 'about_title', 'text', 'عنوان القسم'],
            ['about', 'about_body', 'textarea', 'نبذة تعريفية'],
            ['services', 'services_title', 'text', 'عنوان القسم'],
            ['services', 'services_list', 'list', 'الخدمات'],
            ['gallery', 'gallery_title', 'text', 'عنوان القسم'],
        ];
        for ($i = 1; $i <= $galleryImages; $i++) {
            $slots[] = ['gallery', "gallery_image_{$i}", 'image', "صورة {$i}"];
        }
        $slots = array_merge($slots, [
            ['testimonials', 'testimonials_title', 'text', 'عنوان القسم'],
            ['testimonials', 'testimonials_list', 'list', 'آراء العملاء'],
            ['contact', 'contact_title', 'text', 'عنوان القسم'],
            ['contact', 'contact_note', 'text', 'ملاحظة تواصل'],
            ['contact', 'contact_link', 'link', 'رابط التواصل (واتساب/اتصال)'],
        ]);

        foreach ($slots as $order => [$section, $key, $type, $label]) {
            $template->slots()->create([
                'section_key' => $section, 'key' => $key, 'slot_type' => $type,
                'label_ar' => $label, 'sort_order' => $order + 1,
            ]);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function site(Template $template, array $overrides = [], int $galleryImages = 3): GeneratedSite
    {
        $content = [
            'hero_title' => 'أهلاً بيكم في مطعمنا',
            'hero_subtitle' => 'أكل طازة كل يوم.',
            'hero_image' => '/storage/hero.jpg',
            'about_title' => 'قصتنا',
            'about_body' => 'بدأنا بحلم بسيط.',
            'services_title' => 'خدماتنا',
            'services_list' => ['فطار', 'غدا — من 12 لـ 5', 'عشا'],
            'gallery_title' => 'من مطبخنا',
            'testimonials_title' => 'آراء عملائنا',
            'testimonials_list' => ['أحسن أكل جربته — أحمد س.', 'الخدمة سريعة — منى ع. ★4'],
            'contact_title' => 'احجز طاولتك',
            'contact_note' => 'متواجدين يومياً.',
        ];
        for ($i = 1; $i <= $galleryImages; $i++) {
            $content["gallery_image_{$i}"] = "/storage/g{$i}.jpg";
        }

        $project = Project::factory()->for($template)->create();

        return GeneratedSite::factory()->for($project)->create([
            'content_json' => array_merge($content, $overrides),
            'status' => 'published',
        ]);
    }

    private function show(GeneratedSite $site): TestResponse
    {
        return $this->get(route('site.show', ['siteSlug' => $site->slug]));
    }

    // ---------------------------------------------------------------- المنطق

    public function test_on_color_picks_dark_text_on_light_primaries_and_white_on_dark_ones(): void
    {
        $this->assertSame('#0b0f19', SiteRenderer::onColor('#facc15'));
        $this->assertSame('#0b0f19', SiteRenderer::onColor('#a3e635'));
        $this->assertSame('#ffffff', SiteRenderer::onColor('#4338ca'));
        $this->assertSame('#ffffff', SiteRenderer::onColor('#b45309'));
        // الأغمق في اللوحة بيتستخدم بدل الكحلي الافتراضي لو غامق كفاية.
        $this->assertSame('#10140a', SiteRenderer::onColor('#a3e635', ['#10140a', '#f7fee7']));
        // قيمة مش لون صالح = أبيض (من غير exception).
        $this->assertSame('#ffffff', SiteRenderer::onColor('not-a-color'));
    }

    public function test_testimonial_items_are_parsed_into_quote_name_initial_and_stars(): void
    {
        $this->assertSame(
            ['quote' => 'أحسن أكل جربته من زمان', 'name' => 'أحمد س.', 'initial' => 'أ', 'stars' => null],
            SiteRenderer::parseTestimonial('أحسن أكل جربته من زمان — أحمد س.')
        );
        $this->assertSame(
            ['quote' => 'خدمة ممتازة', 'name' => 'منى ع.', 'initial' => 'م', 'stars' => 5],
            SiteRenderer::parseTestimonial('«خدمة ممتازة» — منى ع. ★5')
        );
        // من غير " — " = اقتباس بس.
        $this->assertSame(
            ['quote' => 'مكان نضيف وخدمة راقية', 'name' => null, 'initial' => null, 'stars' => null],
            SiteRenderer::parseTestimonial('مكان نضيف وخدمة راقية')
        );
        // شرطة جوّه الرأي نفسه وبعدها جملة طويلة مش اسم = متتقسمش.
        $long = SiteRenderer::parseTestimonial('الأكل حلو - بس الانتظار كان طويل شوية يوم الجمعة الصبح');
        $this->assertNull($long['name']);
        // لقب قبل الاسم مايبقاش هو الحرف الأول.
        $this->assertSame('م', SiteRenderer::parseTestimonial('دكتور شاطر — د. محمد')['initial']);
    }

    public function test_service_entries_split_into_title_and_description(): void
    {
        $this->assertSame(['title' => 'كشري', 'desc' => '25 جنيه'], SiteRenderer::splitEntry('كشري — 25 جنيه'));
        $this->assertSame(['title' => 'توصيل سريع', 'desc' => null], SiteRenderer::splitEntry('توصيل سريع'));
    }

    public function test_link_helpers_detect_whatsapp_phone_and_external_links(): void
    {
        $this->assertSame('whatsapp', SiteRenderer::linkType('https://wa.me/201000000000'));
        $this->assertSame('whatsapp', SiteRenderer::linkType('https://api.whatsapp.com/send?phone=20100'));
        $this->assertSame('phone', SiteRenderer::linkType('tel:19019'));
        $this->assertSame('anchor', SiteRenderer::linkType('#contact'));
        $this->assertSame('web', SiteRenderer::linkType('https://example.com'));
        $this->assertTrue(SiteRenderer::isExternal('https://example.com'));
        $this->assertFalse(SiteRenderer::isExternal('tel:19019'));
        $this->assertFalse(SiteRenderer::isExternal('#contact'));
    }

    // الشبكات: بنحاكي توزيع CSS grid (row-major) ونتأكد إن كل صف متملّي بالكامل لأي عدد 1..6.
    public function test_mosaic_and_row_spans_fill_every_row_for_one_to_six_items(): void
    {
        for ($n = 1; $n <= 6; $n++) {
            $mosaic = SiteRenderer::mosaic($n);
            $this->assertCount($n, $mosaic['items']);
            $this->assertGridHasNoHoles($mosaic['items'], 6, 'sm:', "mosaic desktop n={$n}");
            $this->assertGridHasNoHoles($mosaic['items'], 2, '', "mosaic mobile n={$n}");

            $spans = SiteRenderer::rowSpans($n);
            $this->assertCount($n, $spans);
            $this->assertGridHasNoHoles($spans, 6, 'sm:', "rowSpans desktop n={$n}");
            $this->assertGridHasNoHoles($spans, 2, '', "rowSpans mobile n={$n}");
        }

        $this->assertSame('w-full sm:max-w-xl', SiteRenderer::cardWidth(1));
        $this->assertSame('w-full sm:w-[calc(50%-0.75rem)]', SiteRenderer::cardWidth(4));
    }

    /**
     * @param  list<string>  $classes
     */
    private function assertGridHasNoHoles(array $classes, int $columns, string $prefix, string $message): void
    {
        $grid = [];
        $cursorRow = 0;
        $cursorCol = 0;

        // قيمة sm: بتغلب على الشاشات الكبيرة، وإلا القيمة من غير بادئة (زي cascade بتاع Tailwind).
        $span = function (string $class, string $prop) use ($prefix): int {
            $base = null;
            $sm = null;
            foreach (explode(' ', $class) as $token) {
                if (preg_match('/^'.$prop.'-(\d+)$/', $token, $m)) {
                    $base = (int) $m[1];
                }
                if (preg_match('/^sm:'.$prop.'-(\d+)$/', $token, $m)) {
                    $sm = (int) $m[1];
                }
            }

            return $prefix === 'sm:' ? ($sm ?? $base ?? 1) : ($base ?? 1);
        };

        foreach ($classes as $class) {
            $col = $span($class, 'col-span');
            $row = $span($class, 'row-span');
            $this->assertLessThanOrEqual($columns, $col, "{$message}: item wider than the grid");

            // auto-placement (sparse): أول مكان فاضي من المؤشر لقدّام يكفي العنصر.
            [$r, $c] = [$cursorRow, $cursorCol];
            while (true) {
                if ($c + $col > $columns) {
                    $r++;
                    $c = 0;

                    continue;
                }
                $free = true;
                for ($dr = 0; $dr < $row && $free; $dr++) {
                    for ($dc = 0; $dc < $col; $dc++) {
                        if (isset($grid[$r + $dr][$c + $dc])) {
                            $free = false;
                            break;
                        }
                    }
                }
                if ($free) {
                    break;
                }
                $c++;
            }
            for ($dr = 0; $dr < $row; $dr++) {
                for ($dc = 0; $dc < $col; $dc++) {
                    $grid[$r + $dr][$c + $dc] = true;
                }
            }
            [$cursorRow, $cursorCol] = [$r, $c + $col];
        }

        foreach ($grid as $rowIndex => $cells) {
            $this->assertCount($columns, $cells, "{$message}: row {$rowIndex} has a hole");
        }
    }

    // ---------------------------------------------------------------- الرندر

    public function test_contact_section_is_a_cta_even_without_a_link_and_the_hero_gets_a_fallback_button(): void
    {
        $site = $this->site($this->libraryLikeTemplate('modern'));

        $data = app(SiteRenderer::class)->render($site->load('project.template.slots', 'project.variant'));
        $kinds = $data['sections']->pluck('kind', 'key')->all();

        $this->assertSame('cta', $kinds['contact']);
        $this->assertSame('testimonials', $kinds['testimonials']);
        $this->assertSame('contact', $data['contactAnchor']);
        $this->assertSame(['label' => 'اطلب دلوقتي', 'href' => '#contact'], $data['siteMeta']['heroCta']);

        $page = $this->show($site);
        $page->assertOk();
        $page->assertSee('href="#contact"', false);
        $page->assertSee('اطلب دلوقتي');
    }

    public function test_every_layout_renders_a_testimonials_section_and_six_gallery_images_without_errors(): void
    {
        foreach (Template::LAYOUTS as $layout) {
            $site = $this->site($this->libraryLikeTemplate($layout, 6), [], 6);

            $page = $this->show($site);
            $page->assertOk("layout [{$layout}] failed to render");
            $page->assertSee('أحسن أكل جربته', false);
            for ($i = 1; $i <= 6; $i++) {
                $page->assertSee('data-slot="gallery_image_'.$i.'"', false);
            }
        }
    }

    public function test_owned_layouts_render_testimonials_as_cards_with_a_separate_name_and_only_real_stars(): void
    {
        foreach (SiteRenderer::LAYOUTS_WITH_TESTIMONIALS_KIND as $layout) {
            $page = $this->show($this->site($this->libraryLikeTemplate($layout)));

            $page->assertOk();
            $page->assertSee('<figure data-slot="testimonials_list"', false);
            $page->assertSee('<blockquote class="', false);
            // الاسم بيتعرض لوحده مش لازق في الجملة.
            $page->assertDontSee('أحسن أكل جربته — أحمد س.');
            $page->assertSee('أحمد س.');
            // ★4 على الرأي التاني بس.
            $page->assertSee('aria-label="تقييم 4 من 5"', false);
            $page->assertDontSee('aria-label="تقييم 5 من 5"', false);
        }
    }

    public function test_a_google_rating_in_the_testimonials_title_renders_a_rating_badge(): void
    {
        $site = $this->site($this->libraryLikeTemplate('classic'), [
            'testimonials_title' => 'تقييمنا 4.7 ★ على جوجل من 437 تقييم',
            'testimonials_list' => [],
        ]);

        $page = $this->show($site);
        $page->assertOk();
        $page->assertSee('تقييمنا 4.7 ★ على جوجل من 437 تقييم');
        $page->assertSee('aria-label="تقييم 4.7 من 5 على جوجل"', false);
    }

    public function test_floating_whatsapp_button_shows_on_the_public_site_but_never_in_the_live_editor(): void
    {
        $site = $this->site($this->libraryLikeTemplate('modern'), ['contact_link' => 'https://wa.me/201000000000']);

        $public = $this->show($site);
        $public->assertOk();
        $public->assertSee('aria-label="كلّمنا على واتساب"', false);
        // اسم الخانة الإداري مايظهرش على الزرار.
        $public->assertDontSee('رابط التواصل (واتساب/اتصال)');
        $public->assertSee('class="bq-site min-h-screen bq-reveal"', false);

        $editor = $this->actingAs(User::factory()->create())->get(route('projects.site.live-edit', $site->project));
        $editor->assertOk();
        $editor->assertDontSee('aria-label="كلّمنا على واتساب"', false);
        $editor->assertDontSee('bq-reveal', false);
    }

    public function test_phone_links_get_a_call_button_and_never_open_a_new_tab(): void
    {
        $site = $this->site($this->libraryLikeTemplate('split'), ['contact_link' => 'tel:19019']);

        $page = $this->show($site);
        $page->assertOk();
        $page->assertSee('aria-label="اتصل بينا"', false);
        $page->assertSee('href="tel:19019"', false);
        $page->assertDontSee('href="tel:19019" target="_blank"', false);
    }

    public function test_a_website_link_gets_no_floating_button(): void
    {
        $site = $this->site($this->libraryLikeTemplate('bento'), ['contact_link' => 'https://example.com']);

        $page = $this->show($site);
        $page->assertOk();
        $page->assertDontSee('aria-label="كلّمنا على واتساب"', false);
        $page->assertDontSee('aria-label="اتصل بينا"', false);
    }

    public function test_heading_font_comes_from_the_variant_unless_the_site_overrides_the_global_font(): void
    {
        $template = $this->libraryLikeTemplate('magazine');
        $site = $this->site($template);
        // العمود heading_font بتاع عقد 2 (W2) — بنحطه في الذاكرة بس عشان الاختبار يشتغل سواء
        // العمود موجود في الداتابيز ولا لأ.
        $variant = new TemplateVariant(['font' => 'cairo', 'colors_json' => ['primary' => '#facc15']]);
        $variant->setAttribute('heading_font', 'amiri');
        $site->project->setRelation('variant', $variant);
        $site->project->setRelation('template', $template->load('slots'));
        $site->setRelation('project', $site->project);

        $data = app(SiteRenderer::class)->render($site);
        $this->assertSame('amiri', $data['headingFont']);
        // الخلفية الافتراضية (#0b1220) غامقة كفاية فبتتستخدم هي كلون النص فوق الأصفر.
        $this->assertSame('#0b1220', $data['onPrimary']);

        $html = view('site.show', $data)->render();
        $this->assertStringContainsString('--site-font-heading: var(--font-amiri);', $html);
        $this->assertStringContainsString('--site-on-primary: #0b1220;', $html);

        $variant->setAttribute('heading_font', 'evil; } body { display:none');
        $this->assertNull(app(SiteRenderer::class)->render($site)['headingFont']);

        $variant->setAttribute('heading_font', 'amiri');
        $site->font_override = 'tajawal';
        $this->assertNull(app(SiteRenderer::class)->render($site)['headingFont']);
    }

    public function test_layouts_not_yet_supporting_the_testimonials_kind_keep_getting_a_list(): void
    {
        $unsupported = array_values(array_diff(Template::LAYOUTS, SiteRenderer::LAYOUTS_WITH_TESTIMONIALS_KIND));
        if ($unsupported === []) {
            $this->markTestSkipped('كل التصميمات بقت بتدعم قسم الآراء — امسح الشرط والاختبار ده.');
        }

        $site = $this->site($this->libraryLikeTemplate($unsupported[0]));
        $data = app(SiteRenderer::class)->render($site->load('project.template.slots', 'project.variant'));
        $section = $data['sections']->firstWhere('key', 'testimonials');

        $this->assertSame('list', $section['kind']);
        $this->assertSame('أحمد س.', $section['items']->firstWhere('slot.key', 'testimonials_list')['testimonials'][0]['name']);
    }
}
