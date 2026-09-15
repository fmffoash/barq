<?php

namespace App\Http\Middleware;

use App\Models\GeneratedSite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// بيدوّر على الموقع الناتج اللي السب دومين الحالي بيمثّله (باستخدام {siteSlug} اللي بيتحط
// تلقائي من الدومين نفسه في routes/site.php) ويحمّله بكل علاقاته المهمة، عشان الكونترولر
// يرندره على طول من غير أي استعلام إضافي. الموقع المؤرشف بيتعامل معاه زي غير الموجود تماماً —
// مفيش داعي الزائر يعرف الفرق بين "ملوش وجود" و"العميل وقّفه".
class DetectSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('siteSlug');

        $site = GeneratedSite::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'archived')
            ->with(['project.template.slots', 'project.variant'])
            ->first();

        abort_if(! $site, 404);

        $request->attributes->set('site', $site);

        return $next($request);
    }
}
