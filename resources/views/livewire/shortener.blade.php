<div class="panel relative p-5 shadow-soft sm:p-6">
    @if (! $shortUrl)
        <form wire:submit="shorten" class="space-y-4" novalidate>
            <div>
                <label for="hero-url" class="label">Long URL</label>
                <input id="hero-url" type="url" inputmode="url" wire:model="url" class="input py-3" placeholder="https://example.com/a/very/long/path?utm_source=newsletter" autocomplete="off" required>
                @error('url') <p class="error">{{ $message }}</p> @enderror
            </div>
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button type="submit" class="btn-accent w-full py-3" wire:loading.attr="disabled" wire:target="shorten">
                <span wire:loading.remove wire:target="shorten" class="inline-flex items-center gap-2">@svg('phosphor-scissors', 'h-4 w-4') Shorten link</span>
                <span wire:loading wire:target="shorten">Shortening</span>
            </button>
            <p class="text-[13px] text-muted">
                @auth Saved to your dashboard. @else No account needed. Sign up to edit links and see stats. @endauth
            </p>
        </form>
    @else
        <div class="pop">
            <p class="text-[13px] font-medium text-ink-soft">Your short link</p>
            <div class="mt-2 flex items-center gap-2 rounded-box border border-line-strong bg-raised p-1.5 pl-3.5">
                <a href="{{ $shortUrl }}" target="_blank" rel="noopener" class="mono min-w-0 flex-1 truncate text-[16px] font-medium text-ink">{{ str_replace('https://', '', $shortUrl) }}</a>
                <button type="button" class="btn-ink btn-sm" data-copy="{{ $shortUrl }}">@svg('phosphor-copy', 'h-4 w-4') <span data-copy-label>Copy</span></button>
            </div>
            <p class="mt-2 truncate text-[13px] text-muted">Goes to {{ $destination }}</p>

            <div class="mt-5 grid grid-cols-[96px_1fr] items-center gap-4">
                <img src="{{ route('qr', $code) }}" alt="QR code for {{ $shortUrl }}" class="h-24 w-24 rounded-xl border border-line bg-white p-1.5" width="96" height="96">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('qr', $code) }}?download=1" class="btn-ghost btn-sm">@svg('phosphor-download-simple', 'h-4 w-4') QR as SVG</a>
                    <button type="button" wire:click="again" class="btn-ghost btn-sm">Shorten another</button>
                </div>
            </div>
            @guest
                <p class="mt-5 text-[13px] text-muted"><a href="{{ route('register') }}" class="font-medium text-ink underline decoration-accent decoration-2 underline-offset-4">Create free account</a> to track clicks on links like this.</p>
            @endguest
        </div>
    @endif
</div>
