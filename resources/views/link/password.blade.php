<x-layouts.link title="Protected link">
    <div class="panel p-7 shadow-soft">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-accent-soft text-accent">@svg('phosphor-lock-key', 'h-5 w-5')</span>
        <h1 class="mt-5 text-[24px] leading-tight">This link is password protected</h1>
        <p class="mt-2 text-[14px] text-muted">Enter the password the sender gave you to continue.</p>
        <form method="POST" action="{{ url($code) }}" class="mt-6">
            @csrf
            <label class="label" for="password">Password</label>
            <input id="password" name="password" type="password" class="input" autocomplete="off" autofocus required>
            @isset($error) <p class="error">{{ $error }}</p> @endisset
            <button class="btn-ink mt-5 w-full" type="submit">Continue</button>
        </form>
    </div>
</x-layouts.link>
