@props([
    'paginator'
])

@if($paginator->total() > 0)
    <div class="hidden sm:flex items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-400 w-full transition-colors duration-200">
        <div>
            Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $paginator->firstItem() }}</span> 
            sampai <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $paginator->lastItem() }}</span> 
            dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $paginator->total() }}</span> data
        </div>
        <div class="flex items-center gap-1.5">
            @php
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();
                
                if ($lastPage <= 3) {
                    $start = 1;
                    $end = $lastPage;
                } else {
                    if ($currentPage == 1) {
                        $start = 1;
                        $end = 3;
                    } elseif ($currentPage == $lastPage) {
                        $start = $lastPage - 2;
                        $end = $lastPage;
                    } else {
                        $start = $currentPage - 1;
                        $end = $currentPage + 1;
                    }
                }
            @endphp

            @if($paginator->onFirstPage())
                <span class="w-9 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&larr;</span>
            @else
                <button wire:click="previousPage" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&larr;</button>
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page == $currentPage)
                    <span class="w-9 h-9 flex items-center justify-center bg-indigo-600 border border-indigo-700 text-white font-semibold rounded-sm shadow-sm dark:shadow-none select-none">{{ $page }}</span>
                @else
                    <button wire:click="gotoPage({{ $page }})" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-semibold cursor-pointer select-none">{{ $page }}</button>
                @endif
            @endfor

            @if($paginator->hasMorePages())
                <button wire:click="nextPage" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&rarr;</button>
            @else
                <span class="w-9 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&rarr;</span>
            @endif
        </div>
    </div>

    <div class="flex sm:hidden items-center justify-between w-full text-xs text-slate-600 dark:text-slate-400 transition-colors duration-200">
        @if($paginator->onFirstPage())
            <span class="w-28 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&larr;</span>
        @else
            <button wire:click="previousPage" class="w-28 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&larr;</button>
        @endif

        <div class="font-semibold text-slate-800 dark:text-slate-200">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>

        @if($paginator->hasMorePages())
            <button wire:click="nextPage" class="w-28 h-9 flex items-center justify-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm dark:shadow-none rounded-sm font-bold cursor-pointer select-none">&rarr;</button>
        @else
            <span class="w-28 h-9 flex items-center justify-center bg-slate-100 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed shadow-sm dark:shadow-none rounded-sm font-bold select-none">&rarr;</span>
        @endif
    </div>
@endif
