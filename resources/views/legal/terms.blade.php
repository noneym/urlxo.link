<x-layouts.marketing title="Terms of Service">
    <article class="prose-doc wrap max-w-[760px] py-12 lg:py-16">
        <h1 class="text-[40px] leading-[1.05]">Terms of Service</h1>
        <p>Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-19')->format('F j, Y') }}.</p>

        <h2>Using urlxo</h2>
        <p>urlxo lets you create short links on {{ config('urlxo.short_domain') }} that redirect to a destination you choose. By creating a link or an account you agree to these terms.</p>

        <h2>What you may not link to</h2>
        <ul>
            <li>Malware, phishing, or pages that collect credentials or payment details under false pretenses.</li>
            <li>Spam, including links sent in unsolicited bulk messages.</li>
            <li>Content that is illegal where it is hosted or where it is aimed, including child sexual abuse material.</li>
            <li>Content that harasses, threatens or exposes the private data of others.</li>
        </ul>
        <p>We may disable or delete any link, and close any account, that breaks these rules, without notice. Report abuse to <a href="mailto:abuse@urlxo.link" class="text-ink underline decoration-accent underline-offset-4">abuse@urlxo.link</a>.</p>

        <h2>API use</h2>
        <p>API keys are personal to your account. Keep them secret. Rate limits apply and may change to protect the service.</p>

        <h2>Availability</h2>
        <p>The service is provided as is. We work to keep links up, but we do not guarantee uninterrupted availability and are not liable for losses caused by downtime, link removal or data loss.</p>

        <h2>Changes</h2>
        <p>We may update these terms. Continued use after a change means you accept the new version.</p>
    </article>
</x-layouts.marketing>
