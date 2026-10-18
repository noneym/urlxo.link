@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title ?? 'Dashboard'" noindex />
    @livewireStyles
</head>
<body class="min-h-[100dvh]">
<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur-md">
    <nav class="wrap flex h-16 items-center justify-between gap-3">
        <div class="flex items-center gap-5">
            <a href="{{ route('links.index') }}" wire:navigate class="text-ink"><x-logo class="h-7" /></a>
            <div class="hidden items-center gap-1 sm:flex">
                <a href="{{ route('links.index') }}" wire:navigate class="nav-link" @if (request()->routeIs('links.*')) aria-current="page" @endif>Links</a>
                <a href="{{ route('account') }}" wire:navigate class="nav-link" @if (request()->routeIs('account')) aria-current="page" @endif>Account and API</a>
                <a href="{{ route('docs') }}" class="nav-link">Docs</a>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="hidden max-w-[22ch] truncate text-[13px] text-muted md:inline">{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-ghost btn-sm" type="submit">Sign out</button>
            </form>
        </div>
    </nav>
    <div class="wrap flex gap-1 pb-2 sm:hidden">
        <a href="{{ route('links.index') }}" wire:navigate class="nav-link" @if (request()->routeIs('links.*')) aria-current="page" @endif>Links</a>
        <a href="{{ route('account') }}" wire:navigate class="nav-link" @if (request()->routeIs('account')) aria-current="page" @endif>Account and API</a>
    </div>
</header>

<main class="wrap py-8 sm:py-10">
    @if (session('status'))
        <div class="flash-ok">{{ session('status') }}</div>
    @endif
    {{ $slot }}
</main>
@livewireScripts
</body>
</html>
