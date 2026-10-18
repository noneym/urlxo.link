@php
    $copy = [
        'expired' => ['This link has expired.', 'Its owner set an end date and it has passed.'],
        'exhausted' => ['This link reached its click limit.', 'Its owner capped how many times it can be opened.'],
        'disabled' => ['This link is turned off.', 'Its owner has paused it for now.'],
    ][$reason] ?? ['This link is not available.', ''];
@endphp
<x-layouts.link title="Link unavailable">
    <p class="mono text-[13px] text-muted">410</p>
    <h1 class="mt-3 text-[32px] leading-[1.05]">{{ $copy[0] }}</h1>
    <p class="mt-3 text-[15px] text-muted">{{ $copy[1] }} If you expected this to work, ask the person who shared it.</p>
    <a href="{{ route('home') }}" class="btn-ghost mt-7">Go to urlxo.link</a>
</x-layouts.link>
