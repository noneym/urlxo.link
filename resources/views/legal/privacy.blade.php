<x-layouts.marketing title="Privacy Policy">
    <article class="prose-doc wrap max-w-[760px] py-12 lg:py-16">
        <h1 class="text-[40px] leading-[1.05]">Privacy Policy</h1>
        <p>Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-19')->format('F j, Y') }}.</p>

        <h2>Account data</h2>
        <p>If you create an account we store your name, e-mail address and a hashed password. We use your e-mail only to sign you in and to send password reset links.</p>

        <h2>Link data</h2>
        <p>For each link we store the destination URL, its settings and a one-way hash of the IP address that created it, used to rate limit anonymous shortening.</p>

        <h2>Click data</h2>
        <p>When someone opens a short link we record the time, the country (as reported by our network provider), the referring domain, and the device type, browser and operating system derived from the user agent. We do not store IP addresses. To count unique visitors we keep a hash of the IP address and user agent that includes the date, so it changes every day and cannot be linked across days.</p>

        <h2>Cookies</h2>
        <p>We use one session cookie to keep you signed in and to remember that you unlocked a password-protected link. There are no advertising or tracking cookies.</p>

        <h2>Storage and deletion</h2>
        <p>Data is stored on servers in the EU. Deleting a link deletes its click history. To delete your account and all of its data, write to <a href="mailto:privacy@urlxo.link" class="text-ink underline decoration-accent underline-offset-4">privacy@urlxo.link</a>.</p>
    </article>
</x-layouts.marketing>
