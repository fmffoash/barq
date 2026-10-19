<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Template;
use App\Models\TemplateEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// (2026-10-19، طلب فؤاد) إحصائيات القوالب — الأدمن بس. لكل قالب: اتختار كام مرة، شغال دلوقتي على
// كام مشروع، واتشال واتبدّل كام مرة (من سجل template_events)، مع حذف فردي أو جماعي وتشغيل/إيقاف
// أولوية قوالب 3D في الاختيار التلقائي.
class TemplateStatsController extends Controller
{
    private const SORTS = ['chosen', 'current', 'switched', 'rate', 'last', 'name'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:all,3d,regular'],
            'usage' => ['nullable', 'in:all,used,unused,switched'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
        ]);

        $rows = $this->rows();
        $filtered = $this->filter($rows, $filters);
        $sort = $filters['sort'] ?? 'chosen';
        $filtered = $sort === 'name'
            ? $filtered->sortBy('name')
            : $filtered->sortByDesc(fn ($row) => [$row[$sort === 'last' ? 'last_used' : $sort] ?? '', $row['chosen']]);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $paginated = new LengthAwarePaginator(
            $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $used = $rows->where('chosen', '>', 0);

        return view('templates.stats', [
            'rows' => $paginated,
            'filters' => $filters + ['sort' => $sort],
            'categories' => $rows->pluck('category')->filter()->unique()->sort()->values(),
            'prefer3d' => Template::prefers3d(),
            'summary' => [
                'total' => $rows->count(),
                'active' => $rows->where('active', true)->count(),
                'threeD' => $rows->where('is3d', true)->count(),
                'used' => $used->count(),
                'unused' => $rows->count() - $used->count(),
                'projects' => Project::count(),
                'switches' => TemplateEvent::whereNotNull('from_template_id')->count(),
                'threeDShare' => $used->sum('chosen') > 0
                    ? (int) round(100 * $used->where('is3d', true)->sum('chosen') / $used->sum('chosen'))
                    : 0,
            ],
            'topChosen' => $used->sortByDesc('chosen')->take(5)->values(),
            'topSwitched' => $rows->where('switched', '>', 0)->sortByDesc('switched')->take(5)->values(),
        ]);
    }

    public function prefer3d(Request $request): RedirectResponse
    {
        $on = $request->boolean('prefer_3d');
        Setting::put(Template::PREFER_3D_SETTING, $on);

        return back()->with('status', $on
            ? 'تمام — الاختيار التلقائي هيدّي الأولوية لقوالب 3D.'
            : 'تمام — الاختيار التلقائي رجع يختار من كل القوالب.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:700'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = 0;
        $kept = [];
        foreach (Template::whereIn('id', $ids)->get() as $template) {
            if ($template->deletePermanently()) {
                $deleted++;
            } else {
                $kept[] = $template->name;
            }
        }

        $message = "اتمسح {$deleted} قالب.";
        if ($kept !== []) {
            $message .= ' ومامسحتش '.count($kept).' عليهم مشاريع شغالة (أرشفهم بدل كده): '.implode('، ', array_slice($kept, 0, 5)).(count($kept) > 5 ? '…' : '');
        }

        return back()->with('status', $message);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(): Collection
    {
        $chosen = TemplateEvent::query()->whereNotNull('to_template_id')
            ->selectRaw('to_template_id as id, count(*) as total, max(created_at) as last_used')
            ->groupBy('to_template_id')->get()->keyBy('id');
        $switched = TemplateEvent::query()->whereNotNull('from_template_id')
            ->selectRaw('from_template_id as id, count(*) as total')
            ->groupBy('from_template_id')->pluck('total', 'id');

        return Template::query()
            ->select(['id', 'name', 'slug', 'category', 'layout', 'is_active', 'archived_at'])
            ->withCount('projects')
            ->get()
            ->map(function (Template $template) use ($chosen, $switched) {
                $times = (int) ($chosen[$template->id]->total ?? 0);
                $away = (int) ($switched[$template->id] ?? 0);

                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'category' => $template->category,
                    'layout' => Template::layoutLabel($template->layout),
                    'is3d' => Template::is3dLayout($template->layout),
                    'active' => (bool) $template->is_active,
                    'archived' => $template->archived_at !== null,
                    'chosen' => $times,
                    'current' => (int) $template->projects_count,
                    'switched' => $away,
                    // نسبة التبديل: من كل مرة اتختار فيها، كام مرة اتشال بعدها.
                    'rate' => $times > 0 ? (int) round(100 * min($away, $times) / $times) : 0,
                    'last_used' => $chosen[$template->id]->last_used ?? null,
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function filter(Collection $rows, array $filters): Collection
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return $rows
            ->when($q !== '', fn ($c) => $c->filter(fn ($row) => str_contains($row['name'], $q)))
            ->when($filters['category'] ?? null, fn ($c, $category) => $c->where('category', $category))
            ->when(($filters['type'] ?? 'all') === '3d', fn ($c) => $c->where('is3d', true))
            ->when(($filters['type'] ?? 'all') === 'regular', fn ($c) => $c->where('is3d', false))
            ->when(($filters['usage'] ?? 'all') === 'used', fn ($c) => $c->where('chosen', '>', 0))
            ->when(($filters['usage'] ?? 'all') === 'unused', fn ($c) => $c->where('chosen', 0))
            ->when(($filters['usage'] ?? 'all') === 'switched', fn ($c) => $c->where('switched', '>', 0));
    }
}
