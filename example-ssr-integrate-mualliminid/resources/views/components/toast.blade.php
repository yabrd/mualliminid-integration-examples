<div id="toast-container" class="fixed top-6 right-6 z-50 flex flex-col gap-3">
    @if(session('success') || session('error'))
        <div class="toast-item bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border border-slate-200 dark:border-slate-800/80 p-4 pl-6 shadow-2xl flex items-center justify-between gap-4 w-96 rounded-xl transition-all duration-300 translate-x-0 relative">
            <div class="absolute left-0 top-0 bottom-0 w-1 {{ session('success') ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
            <div class="flex-1 min-w-0">
                <span class="text-xs font-bold uppercase tracking-wider block mb-0.5 {{ session('success') ? 'text-emerald-500 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">
                    {{ session('success') ? 'Sukses' : 'Gagal' }}
                </span>
                <span class="text-xs text-slate-600 dark:text-slate-300 block">{{ session('success') ?? session('error') }}</span>
            </div>
            <button onclick="this.closest('.toast-item').remove()" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors shrink-0 p-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif
</div>

<script>
    const setupAutoDismiss = (el) => {
        setTimeout(() => {
            el.classList.add('opacity-0', 'translate-x-10');
            setTimeout(() => el.remove(), 300);
        }, 4000);
    };

    document.querySelectorAll('.toast-item').forEach(setupAutoDismiss);

    window.addEventListener('show-toast', event => {
        const container = document.getElementById('toast-container');
        if (!container) return;

        let payload = {};
        if (event.detail) {
            if (Array.isArray(event.detail) && event.detail[0]) {
                payload = event.detail[0];
            } else if (typeof event.detail === 'object') {
                if ('message' in event.detail) {
                    payload = event.detail;
                } else if (event.detail[0]) {
                    payload = event.detail[0];
                }
            }
        }

        const message = payload.message || 'Terjadi kesalahan';
        const type = payload.type || 'success';
        const isSuccess = type === 'success';

        const toastItem = document.createElement('div');
        toastItem.className = 'toast-item bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border border-slate-200 dark:border-slate-800/80 p-4 pl-6 shadow-2xl flex items-center justify-between gap-4 w-96 rounded-xl transition-all duration-300 translate-x-0 relative';
        
        toastItem.innerHTML = `
            <div class="absolute left-0 top-0 bottom-0 w-1 ${isSuccess ? 'bg-emerald-500' : 'bg-red-500'}"></div>
            <div class="flex-1 min-w-0">
                <span class="text-xs font-bold uppercase tracking-wider block mb-0.5 ${isSuccess ? 'text-emerald-500 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'}">
                    ${isSuccess ? 'Sukses' : 'Gagal'}
                </span>
                <span class="text-xs text-slate-600 dark:text-slate-300 block">${message}</span>
            </div>
            <button onclick="this.closest('.toast-item').remove()" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors shrink-0 p-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        container.appendChild(toastItem);
        setupAutoDismiss(toastItem);
    });
</script>
