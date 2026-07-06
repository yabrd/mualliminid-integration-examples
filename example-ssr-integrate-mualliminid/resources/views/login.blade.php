@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 transition-colors duration-200">
    <div class="fixed top-6 right-6">
        <button id="theme-toggle" class="p-2 border border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-sm transition-colors cursor-pointer shadow-sm dark:shadow-none">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 100 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
        </button>
    </div>

    <x-card class="max-w-sm text-center p-6 pb-4 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 shadow-xl dark:shadow-none">
        <div class="border-b border-slate-200 dark:border-slate-800/60 pb-6 mb-5">
            <h1 class="text-4xl font-black tracking-tight text-slate-800 dark:text-white block w-full text-center uppercase">
                Sign in
            </h1>
            <p class="text-sm text-indigo-700 dark:text-indigo-400 mt-2 uppercase tracking-wide font-bold">
                {{ config('app.name') }}
            </p>
        </div>

        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">
            Aplikasi demo integrasi otentikasi Single Sign-On (SSO) MualliminID berbasis stateless menggunakan Laravel 13 dan Livewire AJAX.
        </p>

        <a href="{{ route('login') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 w-full block rounded-xl transition-all shadow-lg shadow-indigo-600/10 uppercase tracking-wider text-xs mb-2">
            Login MualliminID
        </a>

        <x-slot:footer>
            <p class="text-xs text-slate-400 dark:text-slate-600 text-center w-full">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </x-slot:footer>
    </x-card>
</div>
@endsection
