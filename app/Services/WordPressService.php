<?php

namespace App\Services;

use App\Models\GeneratedSite;
use App\Models\Project;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

// بيكلّم شبكة WordPress Multisite عن طريق mu-plugin مخصّص (شوف docs/wordpress-mu-plugin.php)
// بيعرّض REST endpoints تحت namespace اسمه barq/v1، محمية بتوكن ثابت (Bearer) بدل Application
// Passwords — عشان الشبكة تتأكد إن النداء جاي من برق فعلاً. نفس فلسفة OllamaService بالظبط:
// أي فشل في الاتصال (الشبكة مش متاحة، رد مش متوقّع، الإعدادات ناقصة) بيتسجّل في اللوج وبيرجّع
// false من غير ما يكسر الطلب — الأدمن يقدر يعيد المحاولة تاني من الواجهة وقت ما يحب.
class WordPressService
{
    // بيعمل site جديد فعلي على شبكة الـ Multisite لموقع من نوع "ووردبريس"، ويحفظ بياناته
    // (wp_site_id / wp_site_url / wp_admin_url) على الـ GeneratedSite نفسه. لو الموقع اتعمل
    // بالفعل من قبل، بيرجع true على طول من غير ما يعمل نداء تاني (idempotent).
    public function provisionSite(Project $project, GeneratedSite $site): bool
    {
        if ($project->template->kind !== 'wordpress') {
            throw new RuntimeException('عمل site على شبكة ووردبريس متاح للمشاريع من نوع "ووردبريس" بس.');
        }

        if ($site->isWordPressProvisioned()) {
            return true;
        }

        if (! $this->isConfigured()) {
            Log::warning('WordPress network is not configured — skipping site provisioning.');

            return false;
        }

        try {
            $response = $this->client()->post('/wp-json/barq/v1/sites', [
                'title' => $project->name,
                'slug' => $site->slug,
            ]);

            if (! $response->successful()) {
                Log::warning('WordPress site provisioning failed.', ['status' => $response->status()]);

                return false;
            }

            $siteId = $response->json('site_id');
            $siteUrl = $response->json('site_url');
            $adminUrl = $response->json('admin_url');

            if (! is_numeric($siteId) || ! is_string($siteUrl) || ! is_string($adminUrl)) {
                Log::warning('WordPress site provisioning returned an unexpected response shape.');

                return false;
            }

            $site->forceFill([
                'wp_site_id' => (int) $siteId,
                'wp_site_url' => $siteUrl,
                'wp_admin_url' => $adminUrl,
                'wp_provisioned_at' => now(),
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('WordPress network is unreachable — could not provision site.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    // بيبعت محتوى الموقع الحالي (content_json — نفس المحتوى اللي اتملى بالفورم اليدوي أو
    // باقتراح الذكاء الاصطناعي، صفر فورم منفصل مخصّص لووردبريس) للـ site اللي اتعمل بالفعل
    // على الشبكة. الـ mu-plugin على شبكة الـ WordPress هو المسؤول عن تفسير مفاتيح الخانات
    // المعروفة (site_title / site_tagline / homepage_body) وتجاهل أي مفتاح مش عارفه بأمان.
    public function pushContent(GeneratedSite $site): bool
    {
        if (! $site->isWordPressProvisioned()) {
            Log::warning('Cannot push content — the site is not provisioned on the WordPress network yet.');

            return false;
        }

        if (! $this->isConfigured()) {
            Log::warning('WordPress network is not configured — skipping content push.');

            return false;
        }

        try {
            $response = $this->client()->post("/wp-json/barq/v1/sites/{$site->wp_site_id}/content", [
                'content' => $site->content_json ?? [],
            ]);

            if (! $response->successful()) {
                Log::warning('WordPress content push failed.', ['status' => $response->status()]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('WordPress network is unreachable — could not push content.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isConfigured(): bool
    {
        return filled(config('services.wordpress.network_url')) && filled(config('services.wordpress.shared_secret'));
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.wordpress.network_url'), '/'))
            ->withToken((string) config('services.wordpress.shared_secret'))
            ->timeout(30)
            ->acceptJson();
    }
}
