@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title" :description="$description" />
    @livewireStyles
</head>
<body class="min-h-[100dvh]">
<header class="sticky top-0 z-30 border-b border-line/70 bg-bg/85 backdrop-blur-md">
    <nav class="wrap flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="text-ink" aria-label="urlxo home"><x-logo class="h-7" /></a>
        <div class="hidden items-center gap-1 md:flex">
            <a href="{{ route('home') }}#features" class="nav-link">Features</a>
            <a href="{{ route('home') }}#api" class="nav-link">API</a>
            <a href="{{ route('docs') }}" class="nav-link" @if (request()->routeIs('docs')) aria-current="page" @endif>Docs</a>
            <a href="{{ route('home') }}#faq" class="nav-link">FAQ</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ config('urlxo.github_url') }}" class="nav-link inline-flex items-center gap-1.5" target="_blank" rel="noopener" aria-label="urlxo on GitHub">@svg('phosphor-github-logo', 'h-[18px] w-[18px]')<span class="hidden lg:inline">GitHub</span></a>
            @auth
                <a href="{{ route('links.index') }}" class="btn-ink btn-sm">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="nav-link">Sign in</a>
                @if (config('urlxo.registration_enabled'))
                    <a href="{{ route('register') }}" class="btn-ink btn-sm">Create free account</a>
                @endif
            @endauth
        </div>
    </nav>
</header>

<main>{{ $slot }}</main>

<footer class="mt-24 border-t border-line">
    <div class="wrap grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr]">
        <div>
            <x-logo class="h-7 text-ink" />
            <p class="mt-4 max-w-[38ch] text-[14px] text-muted">Short links, click analytics and a REST API. Free to use, source code on GitHub.</p>
        </div>
        <div class="grid gap-2 text-[14px]">
            <span class="font-medium text-ink">Product</span>
            <a href="{{ route('home') }}#features" class="text-muted hover:text-ink">Features</a>
            <a href="{{ route('docs') }}" class="text-muted hover:text-ink">API docs</a>
            <a href="{{ route('login') }}" class="text-muted hover:text-ink">Sign in</a>
            <a href="{{ config('urlxo.github_url') }}" class="inline-flex items-center gap-1.5 text-muted hover:text-ink" target="_blank" rel="noopener">@svg('phosphor-github-logo', 'h-4 w-4') Source on GitHub</a>
        </div>
        <div class="grid gap-2 text-[14px]">
            <span class="font-medium text-ink">Legal</span>
            <a href="{{ route('legal.terms') }}" class="text-muted hover:text-ink">Terms</a>
            <a href="{{ route('legal.privacy') }}" class="text-muted hover:text-ink">Privacy</a>
            <a href="mailto:abuse@urlxo.link" class="text-muted hover:text-ink">Report abuse</a>
        </div>
    </div>
    <div class="wrap pb-10 text-[13px] text-muted">© {{ date('Y') }} urlxo</div>
</footer>
@livewireScripts
</body>
</html>
