<header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/80 shadow-sm dark:shadow-none transition-colors duration-200">
    <div class="max-w-full mx-auto px-4 py-4 sm:px-5 sm:py-4.5 flex justify-between items-center">
        <div>
            <h1 class="text-xl md:text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-violet-700 dark:from-violet-400 dark:to-indigo-400 bg-clip-text text-transparent">
                Example SSR
            </h1>
        </div>
        <div class="flex items-center gap-3 relative">
            <button id="menu-toggle" class="md:hidden p-2 border border-slate-200 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-sm transition-colors cursor-pointer shadow-sm dark:shadow-none flex items-center justify-center">
                <svg id="menu-hamburger-icon" class="w-6 h-6 transition-all duration-200 transform scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <svg id="menu-close-icon" class="hidden w-6 h-6 transition-all duration-200 transform scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            @if(auth()->check())
            <button id="profile-menu-trigger" class="hidden md:flex items-center justify-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-sm shadow-sm dark:shadow-none h-9 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/80 transition-colors cursor-pointer select-none">
                <span class="text-sm font-bold">
                    {{ auth()->user()->name }}
                </span>
                <svg id="profile-chevron" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            @endif

            <div id="mobile-menu" class="hidden flex-col absolute right-0 top-full mt-2 z-30 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-sm shadow-xl p-0 w-64">
                @if(auth()->check())
                <div class="md:hidden w-full px-4 py-3 border-b border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-950/40 shadow-sm flex flex-col justify-center">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-sm text-slate-600 dark:text-slate-400 font-bold uppercase tracking-wider leading-none">
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                </div>
                <div class="w-full px-4 py-3 border-b border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-950/40 shadow-sm flex flex-col gap-1 text-xs text-slate-500 dark:text-slate-400">
                    <div>{{ auth()->user()->email }}</div>
                    @if(auth()->user()->nbm || auth()->user()->whatsapp_number)
                        <div class="mt-0.5">
                            @if(auth()->user()->nbm) NBM: {{ auth()->user()->nbm }} @endif
                            @if(auth()->user()->nbm && auth()->user()->whatsapp_number) | @endif
                            @if(auth()->user()->whatsapp_number) WA: {{ auth()->user()->whatsapp_number }} @endif
                        </div>
                    @endif
                </div>
                @endif

                <button id="theme-toggle" class="mx-2 mt-2 w-auto px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm transition-colors cursor-pointer shadow-sm dark:shadow-none flex items-center justify-start gap-3">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707-.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    <span class="text-sm font-semibold">Ubah Tema</span>
                </button>

                <form action="{{ route('logout') }}" method="POST" class="contents">
                    @csrf
                    <button type="submit" class="mx-2 mb-2 mt-2 w-auto px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-sm transition-colors cursor-pointer border border-slate-200 dark:border-slate-700/80 shadow-sm dark:shadow-none flex items-center justify-start gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="text-sm font-semibold">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('menu-toggle');
        const triggerBtn = document.getElementById('profile-menu-trigger');
        const mobileMenu = document.getElementById('mobile-menu');

        const toggleDropdown = (e) => {
            e.stopPropagation();
            if (mobileMenu) {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('flex');
            }
            const chevron = document.getElementById('profile-chevron');
            if (chevron) {
                chevron.classList.toggle('rotate-180');
            }
            const hamburger = document.getElementById('menu-hamburger-icon');
            const close = document.getElementById('menu-close-icon');
            if (hamburger && close) {
                hamburger.classList.toggle('hidden');
                close.classList.toggle('hidden');
            }
        };

        if (mobileMenu) {
            if (menuBtn) {
                menuBtn.addEventListener('click', toggleDropdown);
            }
            if (triggerBtn) {
                triggerBtn.addEventListener('click', toggleDropdown);
            }
            document.addEventListener('click', (e) => {
                const clickedMenu = mobileMenu.contains(e.target);
                const clickedMenuBtn = menuBtn && menuBtn.contains(e.target);
                const clickedTriggerBtn = triggerBtn && triggerBtn.contains(e.target);
                if (!clickedMenu && !clickedMenuBtn && !clickedTriggerBtn) {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.classList.remove('flex');
                    const chevron = document.getElementById('profile-chevron');
                    if (chevron) {
                        chevron.classList.remove('rotate-180');
                    }
                    const hamburger = document.getElementById('menu-hamburger-icon');
                    const close = document.getElementById('menu-close-icon');
                    if (hamburger && close) {
                        hamburger.classList.remove('hidden');
                        close.classList.add('hidden');
                    }
                }
            });
        }
    });
</script>
