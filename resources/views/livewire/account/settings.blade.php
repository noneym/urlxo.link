<div class="grid gap-10 lg:grid-cols-[1.25fr_1fr]">
    <section>
        <h1 class="text-[30px] leading-tight">API keys</h1>
        <p class="mt-1 max-w-[60ch] text-[14px] text-muted">Use a key as a Bearer token against <span class="mono text-ink-soft">{{ url('/api/v1') }}</span>. Keys have full access to your links, so keep them out of client-side code.</p>

        <form wire:submit="createToken" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="flex-1">
                <label for="tokenName" class="label">Key name</label>
                <input id="tokenName" type="text" wire:model="tokenName" class="input" placeholder="Zapier, CI, Shortcuts">
                @error('tokenName') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-ink sm:mt-[29px]">@svg('phosphor-key', 'h-4 w-4') Create key</button>
        </form>

        @if ($plainToken)
            <div class="pop mt-4 rounded-box border border-accent bg-accent-soft/50 p-4">
                <p class="text-[14px] font-medium text-ink">Copy this key now. You will not see it again.</p>
                <div class="mt-3 flex items-center gap-2 rounded-box border border-line-strong bg-raised p-1.5 pl-3">
                    <code class="mono min-w-0 flex-1 truncate text-[13px]">{{ $plainToken }}</code>
                    <button type="button" class="btn-ink btn-sm" data-copy="{{ $plainToken }}">@svg('phosphor-copy', 'h-4 w-4') <span data-copy-label>Copy</span></button>
                </div>
            </div>
        @endif

        <div class="mt-6">
            @forelse ($tokens as $token)
                <div wire:key="t-{{ $token->id }}" class="panel mb-2 flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate text-[15px] font-medium">{{ $token->name }}</p>
                        <p class="text-[13px] text-muted">Created {{ $token->created_at->format('M j, Y') }}. {{ $token->last_used_at ? 'Last used '.$token->last_used_at->diffForHumans().'.' : 'Never used.' }}</p>
                    </div>
                    <button type="button" wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke this key? Apps using it stop working immediately." class="btn-danger btn-sm">Revoke</button>
                </div>
            @empty
                <p class="text-[14px] text-muted">No keys yet. Read the <a href="{{ route('docs') }}" class="font-medium text-ink underline decoration-accent underline-offset-4">API docs</a> to see what you can do with one.</p>
            @endforelse
        </div>
    </section>

    <div class="space-y-8">
        <form wire:submit="saveProfile" class="panel space-y-4 p-5 sm:p-6">
            <h2 class="text-[18px]">Profile</h2>
            @if (session('profile')) <div class="flash-ok !mb-0">{{ session('profile') }}</div> @endif
            <div>
                <label for="name" class="label">Name</label>
                <input id="name" type="text" wire:model="name" class="input" autocomplete="name" required>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">E-mail</label>
                <input id="email" type="email" wire:model="email" class="input" autocomplete="email" required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-ghost">Save profile</button>
        </form>

        <form wire:submit="savePassword" class="panel space-y-4 p-5 sm:p-6">
            <h2 class="text-[18px]">Password</h2>
            @if (session('password')) <div class="flash-ok !mb-0">{{ session('password') }}</div> @endif
            <div>
                <label for="current_password" class="label">Current password</label>
                <input id="current_password" type="password" wire:model="current_password" class="input" autocomplete="current-password" required>
                @error('current_password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="new_password" class="label">New password</label>
                <input id="new_password" type="password" wire:model="password" class="input" autocomplete="new-password" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm new password</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn-ghost">Change password</button>
        </form>
    </div>
</div>
