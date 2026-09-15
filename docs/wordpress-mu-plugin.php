<?php
/**
 * Plugin Name: Barq — WordPress Multisite Bridge
 * Description: بيعرّض REST endpoints تحت namespace اسمه barq/v1 عشان أداة برق تقدر تعمل
 *              مواقع ووردبريس جديدة على الشبكة دي وتبعتلها محتوى، بدون أي تدخّل يدوي.
 * Author:      برق (Barq)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ده مش كود لارافيل — الملف ده deliverable بيتنسخ يدوياً لسيرفر شبكة WordPress
 * Multisite منفصلة تماماً عن مشروع برق، مش جزء من الـ runtime بتاع برق نفسه.
 *
 * تعليمات التركيب:
 * ═══════════════════════════════════════════════════════════════════════════
 * 1. الشبكة لازم تكون Multisite فعّالة أصلاً (WP_ALLOW_MULTISITE + MULTISITE
 *    في wp-config.php) — الـ plugin ده مش بيفعّل Multisite بنفسه.
 *
 * 2. انسخ الملف ده بالكامل لمجلد `wp-content/mu-plugins/` على شبكة الـ WordPress
 *    (مش وحش مجلد الـ plugins العادي — mu-plugins بتتفعّل تلقائي بدون أي إعداد
 *    من لوحة تحكم الشبكة، وده مقصود عشان الـ endpoints دي تفضل شغّالة دايماً).
 *
 * 3. ضيف السطر ده في wp-config.php بتاع الشبكة (فوق سطر "That's all, stop editing!")
 *    بنفس القيمة اللي حاطّها WORDPRESS_SHARED_SECRET في ملف .env بتاع برق بالظبط:
 *
 *        define( 'BARQ_SHARED_SECRET', 'حط هنا سلسلة عشوائية طويلة وسرّية' );
 *
 *    ⚠️ صفر قيمة حقيقية متسجّلة في الملف ده عمداً — القيمة الحقيقية بتتحط في
 *    wp-config.php وملف .env بتاع برق بس، مش في أي ملف كود بيتشارك أو يترفع.
 *
 * 4. في ملف .env بتاع برق، حط:
 *        WORDPRESS_NETWORK_URL=https://دومين-الشبكة-الرئيسي
 *        WORDPRESS_SHARED_SECRET=نفس-القيمة-اللي-حطّيتها-فوق-بالظبط
 * ═══════════════════════════════════════════════════════════════════════════
 */

if (! defined('ABSPATH')) {
    exit;
}

// الـ endpoints دي معناها الوحيد إنها تشتغل جوّه شبكة Multisite — لو حد حاول يحط
// الملف ده على تنصيب ووردبريس عادي (single site) بالغلط، بنوقف بصمت من غير أي أثر.
if (! is_multisite()) {
    return;
}

if (! function_exists('barq_check_auth')) {
    // بوابة التحقق الوحيدة لكل نداءات barq/v1 — مقارنة زمنية آمنة (hash_equals) ضد التوكن
    // الثابت المُعرّف في wp-config.php، بدل Application Passwords (أبسط وأوضح لغرض واحد بس).
    function barq_check_auth(WP_REST_Request $request): bool|WP_Error
    {
        if (! defined('BARQ_SHARED_SECRET') || BARQ_SHARED_SECRET === '') {
            return new WP_Error(
                'barq_not_configured',
                'BARQ_SHARED_SECRET مش متعرّف في wp-config.php بتاع الشبكة دي.',
                ['status' => 500]
            );
        }

        $authHeader = $request->get_header('authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return new WP_Error(
                'barq_unauthorized',
                'محتاجين Bearer token في هيدر الـ Authorization.',
                ['status' => 401]
            );
        }

        $token = substr($authHeader, 7);

        if (! hash_equals(BARQ_SHARED_SECRET, $token)) {
            return new WP_Error('barq_unauthorized', 'التوكن غلط.', ['status' => 401]);
        }

        return true;
    }
}

if (! function_exists('barq_resolve_admin_user_id')) {
    // كل site جديد على الشبكة محتاج user_id يبقى الأدمن بتاعه — برق مش بيبعت يوزر معيّن
    // (هو أداة داخلية بتنادي API، مش يوزر حقيقي داخل جوّه ووردبريس)، فبناخد أول Super Admin
    // مُسجّل على الشبكة، وإلا اليوزر رقم 1 كـ fallback أخير (بيبقى موجود على أي تنصيب فريش).
    function barq_resolve_admin_user_id(): int
    {
        $superAdmins = get_super_admins();

        if (! empty($superAdmins)) {
            $user = get_user_by('login', $superAdmins[0]);

            if ($user) {
                return (int) $user->ID;
            }
        }

        return 1;
    }
}

if (! function_exists('barq_create_site')) {
    // بتعمل site حقيقي وكامل على الشبكة (جداول + محتوى افتراضي) عن طريق wpmu_create_blog()
    // — نفس الدالة اللي شاشة "Add New Site" في Network Admin بتستخدمها بالظبط، مش مجرد صف
    // في الداتابيز. الدومين/المسار بيتحدد تلقائي حسب نوع تنصيب الشبكة (subdomain ولا subdirectory)
    // — برق مش المفروض يعرف ولا يخمّن نظام الدومين بتاع الشبكة، هي اللي بتقرر لوحدها.
    function barq_create_site(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        $slug = sanitize_title((string) $request->get_param('slug'));

        if ($title === '' || $slug === '') {
            return new WP_Error('barq_invalid_request', 'محتاجين title و slug الاتنين.', ['status' => 422]);
        }

        $network = get_network();

        if (! $network) {
            return new WP_Error('barq_network_missing', 'معرفناش نحدّد شبكة الـ Multisite.', ['status' => 500]);
        }

        if (is_subdomain_install()) {
            $domain = $slug.'.'.$network->domain;
            $path = '/';
        } else {
            $domain = $network->domain;
            $path = '/'.$slug.'/';
        }

        if (domain_exists($domain, $path, $network->id)) {
            return new WP_Error('barq_site_exists', 'في site بنفس الدومين ده على الشبكة بالفعل.', ['status' => 409]);
        }

        $blogId = wpmu_create_blog(
            $domain,
            $path,
            $title,
            barq_resolve_admin_user_id(),
            ['public' => 1],
            $network->id
        );

        if (is_wp_error($blogId)) {
            return new WP_Error('barq_creation_failed', $blogId->get_error_message(), ['status' => 500]);
        }

        return new WP_REST_Response([
            'site_id' => $blogId,
            'site_url' => get_home_url($blogId),
            'admin_url' => get_admin_url($blogId),
        ], 201);
    }
}

if (! function_exists('barq_apply_homepage_body')) {
    // برق مبيعرفش ولا محتاج يعرف شكل الصفحة الرئيسية الافتراضية بتاعة ووردبريس — بدل ما
    // نحاول نلعب في القالب النشط، بنعمل ونتحكم في صفحة واحدة بس إحنا اللي عارفينها (معلّمة
    // بـ meta key مخصّص) ونحطها كصفحة رئيسية ثابتة. أي تحديث تاني بعد كده بيدوّر على نفس
    // الصفحة دي ويحدّثها، مش بيعمل واحدة جديدة كل مرة.
    function barq_apply_homepage_body(string $body): void
    {
        $existing = get_posts([
            'post_type' => 'page',
            'meta_key' => '_barq_homepage_page',
            'meta_value' => '1',
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);

        if (! empty($existing)) {
            $pageId = $existing[0]->ID;
            wp_update_post(['ID' => $pageId, 'post_content' => $body]);
        } else {
            $pageId = wp_insert_post([
                'post_type' => 'page',
                'post_title' => get_bloginfo('name'),
                'post_content' => $body,
                'post_status' => 'publish',
            ]);

            if (is_wp_error($pageId) || ! $pageId) {
                return;
            }

            update_post_meta($pageId, '_barq_homepage_page', '1');
        }

        update_option('show_on_front', 'page');
        update_option('page_on_front', $pageId);
    }
}

if (! function_exists('barq_push_content')) {
    // بتفسّر مفاتيح المحتوى المعروفة بس (site_title / site_tagline / homepage_body) وتتجاهل
    // أي مفتاح تاني بأمان — برق ممكن يبعت خانات إضافية في المستقبل من غير ما ده يكسر حاجة هنا.
    function barq_push_content(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $siteId = (int) $request->get_param('id');
        $content = $request->get_param('content');

        if (! is_array($content)) {
            $content = [];
        }

        if (! get_site($siteId)) {
            return new WP_Error('barq_site_not_found', 'مفيش site بالـ ID ده على الشبكة.', ['status' => 404]);
        }

        switch_to_blog($siteId);

        if (isset($content['site_title']) && is_string($content['site_title'])) {
            update_option('blogname', sanitize_text_field($content['site_title']));
        }

        if (isset($content['site_tagline']) && is_string($content['site_tagline'])) {
            update_option('blogdescription', sanitize_text_field($content['site_tagline']));
        }

        if (isset($content['homepage_body']) && is_string($content['homepage_body'])) {
            barq_apply_homepage_body(wp_kses_post($content['homepage_body']));
        }

        restore_current_blog();

        return new WP_REST_Response(['ok' => true], 200);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('barq/v1', '/sites', [
        'methods' => 'POST',
        'callback' => 'barq_create_site',
        'permission_callback' => 'barq_check_auth',
        'args' => [
            'title' => ['required' => true, 'type' => 'string'],
            'slug' => ['required' => true, 'type' => 'string'],
        ],
    ]);

    register_rest_route('barq/v1', '/sites/(?P<id>\d+)/content', [
        'methods' => 'POST',
        'callback' => 'barq_push_content',
        'permission_callback' => 'barq_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer'],
            'content' => ['required' => false, 'type' => 'object'],
        ],
    ]);
});
