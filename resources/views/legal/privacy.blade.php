@php($mail = fn ($a) => '<a href="mailto:'.$a.'" class="text-ink underline decoration-accent underline-offset-4">'.$a.'</a>')
<x-layouts.marketing title="Privacy Policy" description="What urlxo collects when you shorten links, open short links, use the API or sign in with Google, and how to get it deleted.">
    <article class="prose-doc wrap max-w-[760px] py-12 lg:py-16">
        <h1 class="text-[40px] leading-[1.05]">Privacy Policy</h1>
        <p>Last updated {{ \Illuminate\Support\Carbon::parse('2026-11-04')->format('F j, Y') }}.</p>
        <p>This policy explains what personal data urlxo collects, why, how long we keep it and what you can ask us to do with it. It covers the website and short links on {{ config('urlxo.short_domain') }}, the REST API, and the sign-in service on {{ config('authhub.domain') }}.</p>

        <h2>Who we are</h2>
        <p>urlxo is operated by the urlxo team, part of xofactory. We are the controller of the data described here. You can reach us about anything in this policy at {!! $mail('privacy@urlxo.link') !!}.</p>

        <h2>Data we collect</h2>
        <h3>Account data</h3>
        <p>If you create an account we store your name, e-mail address and a hashed password (we never store the password itself). We use your e-mail to sign you in, send password reset links and, rarely, tell you about changes that affect your account. We do not send marketing e-mail.</p>

        <h3>Links you create</h3>
        <p>For each link we store the destination URL, the short code, its settings (title, password, expiry, click limit) and the time it was created. For links created without an account we also keep a one-way hash of the creating IP address, used only to rate limit anonymous shortening.</p>

        <h3>Clicks on short links</h3>
        <p>When someone opens a short link we record the time, the country (as reported by our network provider), the referring domain, and the device type, browser and operating system derived from the user agent. We do not store IP addresses. To count unique visitors we keep a hash of the IP address and user agent that includes the date, so it changes every day and cannot be linked across days. Requests from known bots are redirected but not recorded.</p>

        <h3>API keys</h3>
        <p>API keys are stored as hashes. We record when a key was last used so you can spot keys you no longer need.</p>

        <h3 id="sign-in">Sign in with Google ({{ config('authhub.domain') }})</h3>
        <p>urlxo runs a sign-in service that lets you use your Google account to sign in to websites operated by our team. When you choose "Sign in with Google" on one of those sites, you are sent through {{ config('authhub.domain') }} to Google, and Google asks you to share your basic profile with urlxo.</p>
        <ul>
            <li><strong>What we receive from Google:</strong> your Google account ID, name, e-mail address, whether Google has verified that address, your profile picture URL and your language setting. We only request the <code>openid</code>, <code>email</code> and <code>profile</code> scopes. We never receive your Google password and we get no access to Gmail, Drive, contacts or any other Google data.</li>
            <li><strong>What we do with it:</strong> we pass it, in a signed token that expires within two minutes, to the site you asked to sign in to, so that site can create or open your account there. Nothing else.</li>
            <li><strong>What we keep:</strong> nothing about you. The sign-in service does not store your profile, your e-mail address or a login history; it only counts how many sign-ins each site has had. The site you signed in to keeps your account under its own privacy policy.</li>
            <li><strong>Revoking access:</strong> you can remove urlxo from your Google account at any time at <a href="https://myaccount.google.com/connections" class="text-ink underline decoration-accent underline-offset-4" rel="noopener">myaccount.google.com/connections</a>. To delete your account on a site, contact that site.</li>
        </ul>
        <p>urlxo's use and transfer of information received from Google APIs adheres to the <a href="https://developers.google.com/terms/api-services-user-data-policy" class="text-ink underline decoration-accent underline-offset-4" rel="noopener">Google API Services User Data Policy</a>, including the Limited Use requirements. We do not sell Google user data, use it for advertising, or use it to train AI models.</p>

        <h2>Cookies</h2>
        <p>On {{ config('urlxo.short_domain') }} we use a session cookie to keep you signed in and to remember that you unlocked a password-protected link, and a cookie that protects forms against cross-site request forgery. The sign-in service sets one short-lived cookie that ties a sign-in to your browser and is removed when the sign-in finishes. There are no advertising, analytics or tracking cookies.</p>

        <h2>Why we process data</h2>
        <ul>
            <li>To provide the service you asked for: your account, your links, their statistics and sign-in to our sites (performance of a contract).</li>
            <li>To keep the service safe: rate limiting, abuse and fraud prevention, blocking malicious links (legitimate interests).</li>
            <li>To meet legal obligations, for example answering lawful requests from authorities.</li>
        </ul>

        <h2>Who we share it with</h2>
        <p>We do not sell personal data. We use a small number of providers who process data on our behalf: our hosting provider in Germany, Cloudflare for network delivery and protection, and Google for sign-in. When you use the sign-in service, your profile is shared with the site you chose to sign in to. We may disclose data if the law requires it or to protect people from harm.</p>

        <h2>Where data is stored</h2>
        <p>Our servers are in the European Union. Cloudflare and Google may process data in other countries under their own safeguards, such as the EU Standard Contractual Clauses.</p>

        <h2>How long we keep it</h2>
        <ul>
            <li>Account data and links: until you delete them or your account.</li>
            <li>Click statistics: as long as the link exists. Deleting a link deletes its click history.</li>
            <li>Server logs: for a short period, for security and debugging, then deleted.</li>
            <li>Sign-in profiles: not stored.</li>
        </ul>

        <h2>Your rights</h2>
        <p>You can ask us for a copy of your data, to correct it, to delete it, to restrict or object to its processing, or to receive it in a portable format. Write to {!! $mail('privacy@urlxo.link') !!} from the e-mail on your account and we will answer within 30 days. If you are in the EU or UK you can also complain to your local data protection authority.</p>

        <h2>Security</h2>
        <p>All traffic is served over HTTPS. Passwords and API keys are hashed, sign-in tokens are signed and short-lived, and access to production systems is limited to the people who run the service.</p>

        <h2>Children</h2>
        <p>urlxo is not meant for children under 16 and we do not knowingly collect their data. If you believe a child has given us data, contact us and we will delete it.</p>

        <h2>Changes</h2>
        <p>If we change this policy we will update the date at the top. If a change materially affects how we use your data, we will tell account holders by e-mail before it takes effect.</p>

        <h2>Contact</h2>
        <p>Privacy questions and requests: {!! $mail('privacy@urlxo.link') !!}. Abuse reports: {!! $mail('abuse@urlxo.link') !!}.</p>
    </article>
</x-layouts.marketing>
