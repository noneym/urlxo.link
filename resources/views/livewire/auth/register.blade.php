<div>
    <h1 class="text-[28px] leading-tight">Create your account</h1>
    <p class="mt-1 text-[14px] text-muted">Free. No card needed.</p>

    <form wire:submit="register" class="mt-7 space-y-4">
        <div>
            <label class="label" for="name">Name</label>
            <input id="name" type="text" wire:model="name" class="input" autocomplete="name" autofocus required>
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="email">E-mail</label>
            <input id="email" type="email" wire:model="email" class="input" autocomplete="email" required>
            <p class="hint">Used to sign in and to reset your password.</p>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input" autocomplete="new-password" required>
        </div>
        <label class="flex items-start gap-2 text-[14px] text-muted">
            <input type="checkbox" wire:model="accept_terms" class="checkbox mt-0.5">
            <span>I agree to the <a href="{{ route('legal.terms') }}" class="font-semibold text-ink" target="_blank">Terms of Service</a> and <a href="{{ route('legal.privacy') }}" class="font-semibold text-ink" target="_blank">Privacy Policy</a>.</span>
        </label>
        @error('accept_terms') <p class="error -mt-2">{{ $message }}</p> @enderror
        <button type="submit" class="btn-ink w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Create account</span>
            <span wire:loading>Creating</span>
        </button>
    </form>

    <p class="mt-7 text-center text-[14px] text-muted">Already registered? <a href="{{ route('login') }}" class="font-semibold text-ink">Sign in</a></p>
</div>
