@props(['title'])
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
    <a href="{{ route('home') }}" class="mt-12 text-muted hover:text-ink"><x-logo class="h-6" /></a>
</div>
</body>
</html>
