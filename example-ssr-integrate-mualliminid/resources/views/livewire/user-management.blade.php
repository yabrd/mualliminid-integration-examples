<div class="space-y-3">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <x-card>
            <span class="text-xs text-slate-500 block mb-1">Total Pengguna Lokal</span>
            <span class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $stats['total'] }}</span>
        </x-card>
        <x-card>
            <span class="text-xs text-slate-500 block mb-1">Pengguna Ter-sinkronisasi</span>
            <span class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $stats['synced'] }}</span>
        </x-card>
        <x-card>
            <span class="text-xs text-slate-500 block mb-1">Pengguna Tanpa Akses Role</span>
            <span class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $stats['no_role'] }}</span>
        </x-card>
    </div>

    <x-card bodyClass="p-0">
        <x-slot:title>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200">Manajemen Pengguna</h2>
        </x-slot:title>

        <x-slot:headerActions>
            <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-between sm:justify-end">
                <div>
                    <select wire:model.live="perPage" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-700 dark:text-slate-300 text-xs px-2.5 py-2 focus:outline-none focus:border-indigo-500 font-semibold rounded-lg cursor-pointer shadow-sm dark:shadow-none">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} / Halaman</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button wire:click="sync" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-all border border-slate-200 dark:border-slate-800/80 shadow-sm dark:shadow-none flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        Sinkronkan dari SSO
                    </button>
                </div>
            </div>
        </x-slot:headerActions>

        <div class="w-full relative h-[460px]">
            <div wire:loading.flex class="absolute inset-0 w-full h-full bg-white/60 dark:bg-slate-950/60 backdrop-blur-[1px] z-20 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div class="overflow-auto h-full w-full">
                <x-table :headers="$headers" :rows="$localUsers" :columns="$columns" />
            </div>
        </div>

        <x-slot:footer>
            <x-pagination :paginator="$localUsers" />
        </x-slot:footer>
    </x-card>
</div>
