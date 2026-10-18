<div>
    <h1 class="text-[28px] leading-tight">Welcome back</h1>
    <p class="mt-1 text-[14px] text-muted">Sign in to manage your links and API keys.</p>

    <form wire:submit="login" class="mt-7 space-y-4">
        <div>
            <label class="label" for="email">E-mail</label>
            <input id="email" type="email" wire:model="email" class="input" autocomplete="email" autofocus required>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label class="label" for="password">Password</label>
                <a href="{{ route('password.request') }}" class="mb-1.5 text-[13px] font-semibold text-muted hover:text-ink">Forgot?</a>
            </div>
            <input id="password" type="password" wire:model="password" class="input" autocomplete="current-password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-[14px] text-muted">
            <input type="checkbox" wire:model="remember" class="checkbox"> Keep me signed in
        </label>
        <button type="submit" class="btn-ink w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in</span>
        </button>
    </form>

    @if (config('urlxo.registration_enabled'))
        <p class="mt-7 text-center text-[14px] text-muted">New here? <a href="{{ route('register') }}" class="font-semibold text-ink">Create free account</a></p>
    @endif
</div>
