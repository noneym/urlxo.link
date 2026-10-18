@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title ?? 'Account'" noindex />
    @livewireStyles
</head>
<body class="min-h-[100dvh]">
<div class="flex min-h-[100dvh] flex-col items-center justify-center px-4 py-12">
    <a href="{{ route('home') }}" class="mb-10 text-ink"><x-logo class="h-8" /></a>
    <div class="panel w-full max-w-[420px] p-6 shadow-soft sm:p-8">
        @if (session('status'))
            <div class="flash-ok">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </div>
    <p class="mt-8 text-[13px] text-muted">
        <a href="{{ route('legal.terms') }}" class="hover:text-ink">Terms</a>
        <span class="mx-1.5">·</span>
        <a href="{{ route('legal.privacy') }}" class="hover:text-ink">Privacy</a>
    </p>
</div>
@livewireScripts
</body>
</html>
