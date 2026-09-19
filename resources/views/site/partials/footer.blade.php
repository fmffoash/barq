<footer
    class="mt-6 rounded-t-[2.5rem] border-t border-white/5 px-6 py-10 text-center text-sm"
    style="background-color: var(--site-surface); color: var(--site-muted);"
>
    <div class="mx-auto mb-3 h-1 w-16 rounded-full" style="background-color: var(--site-primary);"></div>
    © {{ now()->year }} {{ $project->name }}
</footer>
