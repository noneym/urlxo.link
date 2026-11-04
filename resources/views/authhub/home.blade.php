<x-authhub.layout title="Sign-in service">
    <p class="mono text-[13px] text-muted">{{ config('authhub.domain') }}</p>
    <h1 class="mt-3 text-[32px] leading-[1.05]">urlxo sign-in service</h1>
    <p class="mt-3 text-[15px] text-muted">This address handles "Sign in with Google" for websites operated by the urlxo team. You only land here for a moment while signing in; there is nothing to do on this page.</p>
    <p class="mt-3 text-[15px] text-muted">We receive your Google account ID, name, e-mail address and profile picture, pass them to the site you are signing in to in a signed token that expires within minutes, and do not keep them. Details are in our <a href="{{ rtrim(config('app.url'), '/') }}/legal/privacy#sign-in" class="text-ink underline decoration-accent underline-offset-4">privacy policy</a>.</p>
</x-authhub.layout>
