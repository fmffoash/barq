@extends('layouts.app')

@section('title', 'تعديل ' . $project->name . '')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-400 transition hover:text-amber-400">
                &rarr; {{ $project->name }}
            </a>
        </div>

        <h1 class="mb-1 text-2xl font-bold text-slate-100">تعديل بيانات المشروع</h1>
        <p class="mb-6 text-sm text-slate-500">القالب: {{ $template->name }}</p>

        <form method="POST" action="{{ route('projects.update', $project) }}" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                @if ($template->variants->isNotEmpty())
                    <div>
                        <label for="template_variant_id" class="mb-1.5 block text-sm font-medium text-slate-300">نسخة القالب</label>
                        <select
                            id="template_variant_id"
                            name="template_variant_id"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                        >
                            @php $currentVariant = old('template_variant_id', $project->template_variant_id); @endphp
                            @foreach ($template->variants as $variant)
                                <option value="{{ $variant->id }}" @selected((string) $currentVariant === (string) $variant->id)>
                                    {{ $variant->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('template_variant_id')
                            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @include('projects._fields')

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium text-slate-300">حالة المشروع</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-slate-100 outline-none focus:border-amber-400"
                    >
                        @php $status = old('status', $project->status); @endphp
                        <option value="draft" @selected($status === 'draft')>مسودة</option>
                        <option value="generated" @selected($status === 'generated')>الموقع اتولّد</option>
                        <option value="delivered" @selected($status === 'delivered')>اتسلّم للعميل</option>
                    </select>
                    @error('status')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
@endsection
