<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[30px] leading-tight">Links</h1>
            <p class="mt-1 text-[14px] text-muted">Everything you have shortened on {{ config('urlxo.short_domain') }}.</p>
        </div>
        <dl class="flex gap-8">
            <div><dt class="text-[12px] text-muted">Links</dt><dd class="mono text-[22px] font-medium">{{ number_format($totals['links']) }}</dd></div>
            <div><dt class="text-[12px] text-muted">All-time clicks</dt><dd class="mono text-[22px] font-medium">{{ number_format($totals['clicks']) }}</dd></div>
            <div><dt class="text-[12px] text-muted">Clicks today</dt><dd class="mono text-[22px] font-medium">{{ number_format($totals['today']) }}</dd></div>
        </dl>
    </div>

    <form wire:submit="create" class="panel mt-8 p-4 sm:p-5">
        <div class="grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-start">
            <div>
                <label for="url" class="label">Long URL</label>
                <input id="url" type="url" inputmode="url" wire:model="url" class="input" placeholder="https://..." autocomplete="off" required>
                @error('url') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="code" class="label">Custom alias <span class="font-normal text-muted">(optional)</span></label>
                <div class="flex items-center rounded-box border border-line-strong bg-raised focus-within:border-ink">
                    <span class="mono pl-3.5 text-[13px] text-muted">/</span>
                    <input id="code" type="text" wire:model="code" class="w-full bg-transparent px-1.5 py-2.5 text-[16px] outline-none sm:text-[15px]" placeholder="spring-sale" autocomplete="off">
                </div>
                @error('code') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="md:pt-[29px]">
                <button type="submit" class="btn-accent w-full md:w-auto" wire:loading.attr="disabled" wire:target="create">@svg('phosphor-plus', 'h-4 w-4') Shorten</button>
            </div>
        </div>

        <button type="button" wire:click="$toggle('advanced')" class="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-muted hover:text-ink">
            @svg($advanced ? 'phosphor-caret-up' : 'phosphor-caret-down', 'h-3.5 w-3.5') Title, password, expiry and click limit
        </button>
        @if ($advanced)
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="title" class="label">Title</label>
                    <input id="title" type="text" wire:model="title" class="input" placeholder="Spring newsletter">
                    @error('title') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" type="text" wire:model="password" class="input" autocomplete="off">
                    @error('password') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="expires_at" class="label">Expires at</label>
                    <input id="expires_at" type="datetime-local" wire:model="expires_at" class="input">
                    @error('expires_at') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="max_clicks" class="label">Click limit</label>
                    <input id="max_clicks" type="number" min="1" wire:model="max_clicks" class="input">
                    @error('max_clicks') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif
    </form>

    <div class="mt-8 flex items-center justify-between gap-3">
        <div class="relative w-full max-w-sm">
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">@svg('phosphor-magnifying-glass', 'h-4 w-4')</span>
            <label for="search" class="sr-only">Search links</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search" class="input pl-10" placeholder="Search by alias, URL or title">
        </div>
    </div>

    <div class="mt-4" wire:loading.class="opacity-60" wire:target="search,gotoPage,nextPage,previousPage">
        @forelse ($links as $link)
            <div wire:key="link-{{ $link->id }}" @class(['panel mb-2 grid gap-3 p-4 md:grid-cols-[1fr_auto] md:items-center', 'pop border-accent' => $justCreated === $link->id])>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('links.show', $link) }}" wire:navigate class="mono truncate text-[15px] font-medium text-ink hover:underline">{{ config('urlxo.short_domain') }}/{{ $link->code }}</a>
                        @if (! $link->is_active) <span class="chip">Paused</span> @endif
                        @if ($link->password) <span class="chip">@svg('phosphor-lock-simple', 'h-3 w-3') Password</span> @endif
                        @if ($link->isExpired()) <span class="chip text-danger">Expired</span>
                        @elseif ($link->expires_at) <span class="chip">Until {{ $link->expires_at->format('M j') }}</span> @endif
                        @if ($link->max_clicks) <span class="chip">Max {{ number_format($link->max_clicks) }}</span> @endif
                    </div>
                    <p class="mt-1 truncate text-[13px] text-muted">
                        @if ($link->title) <span class="text-ink-soft">{{ $link->title }}</span> <span class="mx-1">·</span> @endif{{ $link->url }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="mono mr-2 text-[14px] text-ink-soft" title="Clicks">{{ number_format($link->clicks_count) }} <span class="text-muted">{{ Str::plural('click', $link->clicks_count) }}</span></span>
                    <button type="button" class="btn-ghost btn-sm" data-copy="{{ $link->shortUrl() }}">@svg('phosphor-copy', 'h-4 w-4') <span data-copy-label>Copy</span></button>
                    <a href="{{ route('links.show', $link) }}" wire:navigate class="btn-ghost btn-sm">Stats</a>
                    <button type="button" wire:click="toggle({{ $link->id }})" class="btn-ghost btn-sm" title="{{ $link->is_active ? 'Pause' : 'Resume' }}">
                        @svg($link->is_active ? 'phosphor-pause' : 'phosphor-play', 'h-4 w-4')
                        <span class="sr-only">{{ $link->is_active ? 'Pause' : 'Resume' }}</span>
                    </button>
                    <button type="button" wire:click="delete({{ $link->id }})" wire:confirm="Delete this link? It will stop working right away." class="btn-danger btn-sm" title="Delete">
                        @svg('phosphor-trash', 'h-4 w-4') <span class="sr-only">Delete</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="panel flex flex-col items-start gap-3 p-8">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-accent-soft text-accent">@svg('phosphor-link-simple', 'h-5 w-5')</span>
                @if ($search !== '')
                    <h2 class="text-[18px]">No links match "{{ $search }}"</h2>
                    <p class="text-[14px] text-muted">Try part of the alias or the destination domain.</p>
                @else
                    <h2 class="text-[18px]">Your first short link goes here</h2>
                    <p class="max-w-[52ch] text-[14px] text-muted">Paste a URL in the form above. You can also create links from code with the <a href="{{ route('docs') }}" class="font-medium text-ink underline decoration-accent underline-offset-4">REST API</a>.</p>
                @endif
            </div>
        @endforelse

        <div class="mt-6">{{ $links->links() }}</div>
    </div>
</div>
