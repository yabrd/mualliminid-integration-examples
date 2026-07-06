@props([
    'headers' => [],
    'rows' => [],
    'columns' => []
])

<table class="w-full text-left border-collapse">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400 font-bold">
            @foreach($headers as $idx => $header)
                <th class="sticky top-0 bg-slate-100 dark:bg-slate-800 backdrop-blur-[1px] border-b border-slate-200 dark:border-slate-800 py-2 px-2.5 sm:py-2.5 sm:px-3.5 z-10 {{ $idx === 0 ? 'min-w-[130px] sm:min-w-[160px]' : 'min-w-[80px]' }}">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
        @forelse($rows as $row)
            <tr class="bg-white odd:bg-white even:bg-slate-50/40 dark:bg-slate-900 dark:odd:bg-slate-900 dark:even:bg-slate-950/20 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/10 transition-colors duration-150">
                @foreach($columns as $col)
                    @php
                        $key = is_array($col) ? $col['key'] : $col;
                        $type = is_array($col) ? ($col['type'] ?? 'text') : 'text';
                        $val = $row->$key ?? '-';
                    @endphp
                    <td class="py-2 px-2.5 sm:py-2.5 sm:px-3.5 {{ $type === 'title' ? 'min-w-[130px] sm:min-w-[160px]' : 'min-w-[80px]' }}">
                        @if($type === 'title')
                            <span class="font-medium text-slate-800 dark:text-slate-200 text-xs sm:text-[15px]">{{ $val }}</span>
                        @elseif($type === 'badge')
                            <span class="bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-medium rounded">
                                {{ $val }}
                            </span>
                        @elseif($type === 'code')
                            <span class="font-mono text-xs sm:text-[14px] text-slate-600 dark:text-slate-300">{{ $val }}</span>
                        @else
                            <span class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm">{{ $val }}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) }}" class="py-12 text-center text-slate-400 dark:text-slate-500">
                    Belum ada data. Silakan lakukan sinkronisasi terlebih dahulu.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
