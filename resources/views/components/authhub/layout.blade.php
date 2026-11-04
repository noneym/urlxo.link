@props(['title'])
@php($site = rtrim(config('app.url'), '/'))
<!DOCTYPE html>
<html lang="en">
<head>
    <x-head :title="$title" noindex />
</head>
<body class="min-h-[100dvh]">
<div class="flex min-h-[100dvh] flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-[440px] rise">
        {{ $slot }}
    </div>
    <div class="mt-12 flex flex-col items-center gap-3 text-[13px] text-muted">
        <a href="{{ $site }}/" class="hover:text-ink"><x-logo class="h-6" /></a>
        <div class="flex gap-4">
            <a href="{{ $site }}/legal/privacy" class="hover:text-ink">Privacy</a>
            <a href="{{ $site }}/legal/terms" class="hover:text-ink">Terms</a>
            <a href="mailto:privacy@urlxo.link" class="hover:text-ink">Contact</a>
        </div>
    </div>
</div>
</body>
</html>
