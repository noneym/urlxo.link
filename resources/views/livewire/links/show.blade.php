@php
    $max = max(1, collect($stats['daily'])->max('clicks'));
    $short = config('urlxo.short_domain').'/'.$link->code;
    $flag = fn (string $cc) => strlen($cc) === 2 ? mb_chr(0x1F1E6 + ord($cc[0]) - 65).mb_chr(0x1F1E6 + ord($cc[1]) - 65) : '';
@endphp
<div>
    <a href="{{ route('links.index') }}" wire:navigate class="inline-flex items-center gap-1 text-[13px] font-medium text-muted hover:text-ink">@svg('phosphor-arrow-left', 'h-3.5 w-3.5') All links</a>

    <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="mono truncate text-[26px] font-medium tracking-[-0.02em] sm:text-[30px]">{{ $short }}</h1>
                @if (! $link->is_active) <span class="chip">Paused</span> @endif
                @if ($link->isExpired()) <span class="chip text-danger">Expired</span> @endif
            </div>
            @if ($link->title) <p class="mt-1 text-[15px] text-ink-soft">{{ $link->title }}</p> @endif
            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-[14px] text-muted hover:text-ink">{{ $link->url }}</a>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="btn-ink btn-sm" data-copy="{{ $link->shortUrl() }}">@svg('phosphor-copy', 'h-4 w-4') <span data-copy-label>Copy link</span></button>
                <button type="button" wire:click="$set('editing', true)" class="btn-ghost btn-sm">@svg('phosphor-pencil-simple', 'h-4 w-4') Edit</button>
                <button type="button" wire:click="toggle" class="btn-ghost btn-sm">@svg($link->is_active ? 'phosphor-pause' : 'phosphor-play', 'h-4 w-4') {{ $link->is_active ? 'Pause' : 'Resume' }}</button>
                <button type="button" wire:click="delete" wire:confirm="Delete this link and all of its stats?" class="btn-danger btn-sm">@svg('phosphor-trash', 'h-4 w-4') Delete</button>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <img src="{{ route('qr', $link->code) }}" alt="QR code" class="h-28 w-28 rounded-xl border border-line bg-white p-1.5" width="112" height="112">
            <a href="{{ route('qr', $link->code) }}?download=1" class="btn-ghost btn-sm">@svg('phosphor-download-simple', 'h-4 w-4') SVG</a>
        </div>
    </div>

    @if (session('saved')) <div class="flash-ok mt-6">{{ session('saved') }}</div> @endif

    @if ($editing)
        <form wire:submit="save" class="panel pop mt-6 grid gap-4 p-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="e-url" class="label">Destination URL</label>
                <input id="e-url" type="url" wire:model="url" class="input" required>
                @error('url') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="e-code" class="label">Alias</label>
                <input id="e-code" type="text" wire:model="code" class="input mono" required>
                <p class="hint">Changing it breaks the old short link.</p>
                @error('code') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="e-title" class="label">Title</label>
                <input id="e-title" type="text" wire:model="title" class="input">
                @error('title') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="e-password" class="label">{{ $link->password ? 'New password' : 'Password' }}</label>
                <input id="e-password" type="text" wire:model="password" class="input" autocomplete="off" @disabled($removePassword)>
                @if ($link->password)
                    <label class="mt-2 flex items-center gap-2 text-[13px] text-muted"><input type="checkbox" wire:model.live="removePassword" class="checkbox"> Remove the password</label>
                @else
                    <p class="hint">Leave empty for a public link.</p>
                @endif
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="e-exp" class="label">Expires at</label>
                    <input id="e-exp" type="datetime-local" wire:model="expires_at" class="input">
                    @error('expires_at') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="e-max" class="label">Click limit</label>
                    <input id="e-max" type="number" min="1" wire:model="max_clicks" class="input">
                    @error('max_clicks') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-2 md:col-span-2">
                <button type="submit" class="btn-accent" wire:loading.attr="disabled" wire:target="save">Save changes</button>
                <button type="button" wire:click="cancel" class="btn-ghost">Cancel</button>
            </div>
        </form>
    @endif

    <section class="panel mt-8 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <dl class="flex gap-10">
                <div><dt class="text-[12px] text-muted">Clicks</dt><dd class="mono text-[28px] font-medium">{{ number_format($stats['total']) }}</dd></div>
                <div><dt class="text-[12px] text-muted">Unique visitors</dt><dd class="mono text-[28px] font-medium">{{ number_format($stats['unique']) }}</dd></div>
                <div><dt class="text-[12px] text-muted">All time</dt><dd class="mono text-[28px] font-medium">{{ number_format($link->clicks_count) }}</dd></div>
            </dl>
            <div class="flex rounded-full border border-line p-1" role="tablist" aria-label="Period">
                @foreach ([7 => '7d', 30 => '30d', 90 => '90d', 365 => '1y'] as $d => $label)
                    <button type="button" wire:click="setDays({{ $d }})" role="tab" aria-selected="{{ $days === $d ? 'true' : 'false' }}" @class(['rounded-full px-3 py-1 text-[13px] font-medium transition', 'bg-ink text-bg' => $days === $d, 'text-muted hover:text-ink' => $days !== $d])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="mt-6" wire:loading.class="opacity-50" wire:target="setDays">
            @if ($stats['total'] === 0)
                <div class="flex h-40 flex-col items-center justify-center rounded-box border border-dashed border-line-strong text-center">
                    <p class="text-[14px] font-medium text-ink-soft">No clicks in this period yet</p>
                    <p class="mt-1 text-[13px] text-muted">Share the link and the chart fills in within seconds.</p>
                </div>
            @else
                <div class="flex h-40 items-end gap-[2px]" aria-label="Clicks per day">
                    @foreach ($stats['daily'] as $day)
                        <div class="group relative flex h-full flex-1 items-end" title="{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M j') }}: {{ $day['clicks'] }} clicks">
                            <div class="w-full rounded-t-[3px] {{ $day['clicks'] ? 'bg-accent' : 'bg-line' }} transition group-hover:opacity-80" style="height: {{ $day['clicks'] ? max(4, round($day['clicks'] / $max * 100)) : 2 }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mono mt-2 flex justify-between text-[11px] text-muted">
                    <span>{{ \Illuminate\Support\Carbon::parse($stats['from'])->format('M j') }}</span>
                    <span>{{ \Illuminate\Support\Carbon::parse($stats['to'])->format('M j') }}</span>
                </div>
            @endif
        </div>
    </section>

    @if ($stats['total'] > 0)
        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Countries', $stats['countries'], true],
                ['Referrers', $stats['referrers'], false],
                ['Devices', $stats['devices'], false],
                ['Browsers', $stats['browsers'], false],
                ['Operating systems', $stats['os'], false],
            ] as [$heading, $rows, $isCountry])
                <section class="panel p-5">
                    <h2 class="text-[15px]">{{ $heading }}</h2>
                    <ul class="mt-3 space-y-2.5">
                        @foreach ($rows as $row)
                            <li class="text-[14px]">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate text-ink-soft">@if ($isCountry && $row['key'] !== 'Unknown'){{ $flag($row['key']) }} @endif{{ $heading === 'Devices' ? ucfirst($row['key']) : $row['key'] }}</span>
                                    <span class="mono text-ink">{{ number_format($row['clicks']) }}</span>
                                </div>
                                <div class="mt-1 h-[3px] rounded-full bg-accent" style="width: {{ round($row['clicks'] / max(1, $stats['total']) * 100) }}%"></div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif

    <p class="mt-6 text-[13px] text-muted">Created {{ $link->created_at->format('M j, Y') }}. Bots and link previews are not counted. Country comes from Cloudflare.</p>
</div>
