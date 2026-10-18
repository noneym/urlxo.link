<x-layouts.link title="Link not found">
    <p class="mono text-[13px] text-muted">404</p>
    <h1 class="mt-3 text-[32px] leading-[1.05]">This short link does not exist.</h1>
    <p class="mt-3 text-[15px] text-muted">Check the address for a typo. Short links are case sensitive, so <span class="mono">aB3x</span> and <span class="mono">ab3x</span> are different.</p>
    <a href="{{ route('home') }}" class="btn-ink mt-7">Make a short link</a>
</x-layouts.link>
