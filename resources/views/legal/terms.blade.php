@php($mail = fn ($a) => '<a href="mailto:'.$a.'" class="text-ink underline decoration-accent underline-offset-4">'.$a.'</a>')
<x-layouts.marketing title="Terms of Service" description="The rules for using urlxo short links, the API and the urlxo sign-in service.">
    <article class="prose-doc wrap max-w-[760px] py-12 lg:py-16">
        <h1 class="text-[40px] leading-[1.05]">Terms of Service</h1>
        <p>Last updated {{ \Illuminate\Support\Carbon::parse('2026-11-04')->format('F j, Y') }}.</p>
        <p>These terms apply to the urlxo website and short links on {{ config('urlxo.short_domain') }}, the urlxo REST API and the urlxo sign-in service on {{ config('authhub.domain') }}, together "the service". By creating a link, an account or an API key, or by signing in through the sign-in service, you agree to them. If you do not agree, do not use the service.</p>

        <h2>The service</h2>
        <p>urlxo lets you create short links that redirect to a destination you choose, see statistics about clicks on them, and manage them through a REST API. The sign-in service lets you sign in to websites operated by the urlxo team with your Google account. The service is free; if we add paid features, these terms will say so.</p>

        <h2>Who can use it</h2>
        <p>You must be at least 16 years old, or older if the law where you live requires it, and able to agree to these terms. If you use urlxo for an organisation, you confirm you are allowed to accept these terms for it.</p>

        <h2>Your account</h2>
        <ul>
            <li>Give a real e-mail address you control, and keep your password and API keys secret.</li>
            <li>You are responsible for everything done with your account and keys. Tell us at {!! $mail('abuse@urlxo.link') !!} if you think someone else has access.</li>
            <li>You can delete your account at any time by writing to {!! $mail('privacy@urlxo.link') !!}.</li>
        </ul>

        <h2>Acceptable use</h2>
        <p>You may not use urlxo to link to, or otherwise promote:</p>
        <ul>
            <li>Malware, phishing, or pages that collect credentials or payment details under false pretenses.</li>
            <li>Spam, including links sent in unsolicited bulk messages or posted by bots.</li>
            <li>Content that is illegal where it is hosted or where it is aimed, including child sexual abuse material.</li>
            <li>Content that harasses, threatens, incites violence against or exposes the private data of others.</li>
            <li>Content that infringes someone else's copyright, trademark or other rights.</li>
        </ul>
        <p>You also may not try to break, overload or get around the limits of the service, scrape it, probe it for vulnerabilities without our permission, or use short links to hide where a link leads in order to deceive people.</p>

        <h2>Enforcement</h2>
        <p>We may disable or delete any link, revoke any API key and close any account that breaks these terms, in most cases without notice, and we may block destination domains that are repeatedly abused. Report abuse to {!! $mail('abuse@urlxo.link') !!} with the short link and a short description.</p>

        <h2>API</h2>
        <p>API keys are personal to your account. Rate limits apply and may change to protect the service. Do not put keys in public code or client-side applications. We may change the API; we will try to give notice before breaking changes to <code>/api/v1</code>.</p>

        <h2>Sign-in service</h2>
        <p>When you sign in through {{ config('authhub.domain') }}, urlxo receives your basic Google profile and passes it to the site you chose, as described in the <a href="{{ route('legal.privacy') }}#sign-in" class="text-ink underline decoration-accent underline-offset-4">privacy policy</a>. Your account on that site, and what you do there, is governed by that site's own terms. Google's terms continue to apply to your Google account. You may not use the sign-in service to impersonate others or to access sites in ways they do not allow.</p>

        <h2>Your content</h2>
        <p>You keep all rights to the links and titles you create. You give us the permission we need to store them, redirect visitors and show you statistics. You confirm you are allowed to share the destinations you link to.</p>

        <h2>Our software</h2>
        <p>The urlxo source code is published on <a href="{{ config('urlxo.github_url') }}" class="text-ink underline decoration-accent underline-offset-4" rel="noopener">GitHub</a> under its own licence. The urlxo name and logo are ours; please do not use them in a way that suggests we endorse you.</p>

        <h2>Availability and changes</h2>
        <p>We work to keep links up and redirects fast, but the service is provided "as is" and "as available". We do not guarantee it will be uninterrupted or error free, and we may change, suspend or stop any part of it. If we shut the service down we will give account holders at least 30 days' notice where we can.</p>

        <h2>Liability</h2>
        <p>To the extent the law allows, urlxo is not liable for indirect or consequential losses, lost profits or lost data, or for losses caused by downtime, link removal, or the content of sites that short links lead to. Nothing in these terms limits liability that cannot be limited by law.</p>

        <h2>Ending these terms</h2>
        <p>You can stop using the service at any time. We may end your access if you break these terms. Sections that by their nature should survive, such as liability, keep applying after your access ends.</p>

        <h2>Changes to these terms</h2>
        <p>We may update these terms. We will change the date at the top and, for material changes, e-mail account holders before they take effect. Continued use after a change means you accept the new version.</p>

        <h2>Contact</h2>
        <p>Questions about these terms: {!! $mail('privacy@urlxo.link') !!}. Abuse reports: {!! $mail('abuse@urlxo.link') !!}.</p>
    </article>
</x-layouts.marketing>
