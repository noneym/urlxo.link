<x-authhub.layout title="Sign-in self test">
    <p class="mono text-[13px] text-muted">Self test</p>
    @if ($error)
        <h1 class="mt-3 text-[32px] leading-[1.05]">Sign-in cancelled.</h1>
        <p class="mt-3 text-[15px] text-muted">{{ $error }}</p>
    @else
        @php($ok = ! in_array(false, $checks, true))
        <h1 class="mt-3 text-[32px] leading-[1.05]">{{ $ok ? 'The hub works.' : 'Token check failed.' }}</h1>
        <ul class="mt-5 grid gap-1 text-[14px]">
            @foreach ($checks as $label => $passed)
                <li class="{{ $passed ? 'text-ink' : 'text-accent' }}">{{ $passed ? '✓' : '✗' }} {{ $label }}</li>
            @endforeach
        </ul>
        @if ($claims)
            <pre class="mono mt-5 overflow-x-auto rounded-box border border-line bg-raised p-4 text-[12px] leading-relaxed">{{ json_encode($claims, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    @endif
    <a href="/selftest" class="btn-ink mt-7">Run again</a>
</x-authhub.layout>
