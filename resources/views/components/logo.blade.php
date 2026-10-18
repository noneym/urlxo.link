@props(['class' => 'h-7'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 '.$class]) }}>
    <svg viewBox="0 0 32 32" class="h-full w-auto" aria-hidden="true">
        <circle cx="12" cy="16" r="8.5" fill="none" stroke="currentColor" stroke-width="3"/>
        <circle cx="21" cy="16" r="8.5" fill="var(--accent)"/>
    </svg>
    <span class="text-[19px] font-semibold tracking-[-0.04em]">urlxo<span class="text-muted">.link</span></span>
</span>
