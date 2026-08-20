@php
    $linkBase = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $linkIdle = 'text-brand-100/70 hover:bg-white/5 hover:text-white';
    $linkActive = 'bg-brand-500 text-white shadow-card';
@endphp

<a href="{{ route('validator.dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('validator.dashboard') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l8.25-8.25L20.25 12M4.5 9.75v9.75a.75.75 0 00.75.75H9a.75.75 0 00.75-.75v-4.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v4.5c0 .414.336.75.75.75h3.75a.75.75 0 00.75-.75V9.75" /></svg>
    Dashboard
</a>

<a href="{{ route('validator.usulan-program-kerja.index') }}" class="{{ $linkBase }} {{ request()->routeIs('validator.usulan-program-kerja.*') ? $linkActive : $linkIdle }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    Usulan Program Kerja
</a>