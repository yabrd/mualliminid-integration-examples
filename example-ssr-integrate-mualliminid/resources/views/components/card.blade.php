@props([
    'title' => null,
    'subtitle' => null,
    'bodyClass' => 'p-4 sm:p-5',
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 shadow-sm dark:shadow-none w-full rounded-sm transition-colors duration-200 overflow-hidden']) }}>
    @if($title || isset($headerActions))
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700/80 shadow-[0_4px_12px_rgba(0,0,0,0.05)] relative z-10 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex-1">
                @if($title)
                    {{ $title }}
                @endif
                @if($subtitle)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($headerActions))
                <div class="shrink-0">
                    {{ $headerActions }}
                </div>
            @endif
        </header>
    @endif

    <div class="{{ ($title || isset($headerActions) || isset($footer)) ? 'bg-slate-50/50 dark:bg-slate-950/30' : '' }} {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700/80 shadow-[0_-4px_12px_rgba(0,0,0,0.05)] relative z-10 p-4 sm:p-5">
            {{ $footer }}
        </footer>
    @endif
</div>
