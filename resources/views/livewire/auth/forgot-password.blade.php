<div>
    <h1 class="text-[28px] leading-tight">Reset your password</h1>
    <p class="mt-1 text-[14px] text-muted">Enter your e-mail and we will send a link to choose a new one.</p>

    @if ($sent)
        <div class="flash-ok mt-6">{{ $sent }}</div>
    @endif

    <form wire:submit="send" class="mt-7 space-y-4">
        <div>
            <label class="label" for="email">E-mail</label>
            <input id="email" type="email" wire:model="email" class="input" autocomplete="email" autofocus required>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-ink w-full" wire:loading.attr="disabled">Send reset link</button>
    </form>

    <p class="mt-7 text-center text-[14px] text-muted"><a href="{{ route('login') }}" class="font-semibold text-ink">Back to sign in</a></p>
</div>
