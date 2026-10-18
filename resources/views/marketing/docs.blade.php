@php $api = url('/api/v1'); $domain = config('urlxo.short_domain'); @endphp
<x-layouts.marketing title="API docs" description="urlxo REST API reference: create, update, delete short links and read click stats with Bearer keys.">
    <div class="wrap grid gap-12 py-12 lg:grid-cols-[200px_1fr] lg:py-16">
        <aside class="hidden lg:block">
            <nav class="sticky top-24 grid gap-1 text-[14px]">
                @foreach (['auth' => 'Authentication', 'create' => 'Create a link', 'list' => 'List links', 'get' => 'Get a link', 'update' => 'Update a link', 'delete' => 'Delete a link', 'stats' => 'Click stats', 'object' => 'Link object', 'errors' => 'Errors and limits'] as $id => $label)
                    <a href="#{{ $id }}" class="rounded-full px-3 py-1.5 text-muted hover:bg-raised hover:text-ink">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>

        <article class="prose-doc min-w-0 max-w-[760px]">
            <h1 class="text-[40px] leading-[1.05]">API docs</h1>
            <p>The urlxo API is JSON over HTTPS. Base URL:</p>
            <pre><code>{{ $api }}</code></pre>

            <h2 id="auth" class="scroll-mt-24">Authentication</h2>
            <p>Create a key under <a href="{{ route('account') }}" class="font-medium text-ink underline decoration-accent underline-offset-4">Account and API</a> and send it as a Bearer token. Keys act as your account, so keep them on the server.</p>
            <pre><code>curl {{ $api }}/me \
  -H "Authorization: Bearer $URLXO_KEY" \
  -H "Accept: application/json"</code></pre>

            <h2 id="create" class="scroll-mt-24">Create a link</h2>
            <p><code>POST /links</code></p>
            <table>
                <tr><th>Field</th><th>Type</th><th>Notes</th></tr>
                <tr><td><code>url</code></td><td>string</td><td>Required. http or https, up to 2048 characters. A missing scheme becomes https.</td></tr>
                <tr><td><code>code</code></td><td>string</td><td>Optional alias, 3 to 32 of <code>A-Z a-z 0-9 _ -</code>. Case sensitive. Generated when empty.</td></tr>
                <tr><td><code>title</code></td><td>string</td><td>Optional label for your dashboard.</td></tr>
                <tr><td><code>password</code></td><td>string</td><td>Optional. Visitors must enter it before the redirect.</td></tr>
                <tr><td><code>expires_at</code></td><td>ISO 8601</td><td>Optional. Must be in the future. After it, the link returns 410.</td></tr>
                <tr><td><code>max_clicks</code></td><td>integer</td><td>Optional cap on counted clicks.</td></tr>
                <tr><td><code>is_active</code></td><td>boolean</td><td>Defaults to <code>true</code>.</td></tr>
            </table>
            <pre><code>curl -X POST {{ $api }}/links \
  -H "Authorization: Bearer $URLXO_KEY" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/launch","code":"launch","expires_at":"2027-01-31T23:59:00Z"}'</code></pre>
            <p>Returns <code>201</code> with the <a href="#object" class="text-ink underline decoration-accent underline-offset-4">link object</a>.</p>

            <h2 id="list" class="scroll-mt-24">List links</h2>
            <p><code>GET /links?q=launch&amp;per_page=25&amp;page=1</code></p>
            <p><code>q</code> searches alias, destination and title. <code>per_page</code> goes up to 100. The response has <code>data</code> and <code>meta</code> with <code>current_page</code>, <code>per_page</code>, <code>total</code> and <code>last_page</code>.</p>

            <h2 id="get" class="scroll-mt-24">Get a link</h2>
            <p><code>GET /links/{code}</code></p>

            <h2 id="update" class="scroll-mt-24">Update a link</h2>
            <p><code>PATCH /links/{code}</code> with any of the create fields. Send <code>"password": null</code> to remove a password and <code>"expires_at": null</code> to remove the expiry. Changing <code>code</code> moves the link and the old short URL stops working.</p>
            <pre><code>curl -X PATCH {{ $api }}/links/launch \
  -H "Authorization: Bearer $URLXO_KEY" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/launch-v2"}'</code></pre>

            <h2 id="delete" class="scroll-mt-24">Delete a link</h2>
            <p><code>DELETE /links/{code}</code> returns <code>204</code>. Its click history is deleted too.</p>

            <h2 id="stats" class="scroll-mt-24">Click stats</h2>
            <p><code>GET /links/{code}/stats?days=30</code>, where <code>days</code> is 1 to 365 and includes today (UTC).</p>
            <pre><code>{
  "data": {
    "code": "launch",
    "all_time_clicks": 1284,
    "from": "2026-09-20",
    "to": "2026-10-19",
    "total": 912,
    "unique": 733,
    "daily": [{ "date": "2026-09-20", "clicks": 18 }],
    "countries": [{ "key": "NL", "clicks": 301 }],
    "referrers": [{ "key": "Direct", "clicks": 410 }],
    "devices": [{ "key": "mobile", "clicks": 577 }],
    "browsers": [{ "key": "Chrome", "clicks": 488 }],
    "os": [{ "key": "iOS", "clicks": 341 }]
  }
}</code></pre>
            <p>Countries are ISO 3166-1 alpha-2 codes. Bots, crawlers and chat link previews are not counted.</p>

            <h2 id="object" class="scroll-mt-24">Link object</h2>
            <pre><code>{
  "id": 42,
  "code": "launch",
  "short_url": "https://{{ $domain }}/launch",
  "url": "https://example.com/launch",
  "title": null,
  "has_password": false,
  "expires_at": "2027-01-31T23:59:00+00:00",
  "max_clicks": null,
  "clicks": 0,
  "is_active": true,
  "last_clicked_at": null,
  "created_at": "2026-10-19T09:12:44+00:00",
  "updated_at": "2026-10-19T09:12:44+00:00"
}</code></pre>

            <h2 id="errors" class="scroll-mt-24">Errors and limits</h2>
            <table>
                <tr><th>Status</th><th>Meaning</th></tr>
                <tr><td><code>401</code></td><td>Missing or revoked key.</td></tr>
                <tr><td><code>404</code></td><td>No link with that code in your account.</td></tr>
                <tr><td><code>422</code></td><td>Validation failed. <code>errors</code> maps each field to its messages.</td></tr>
                <tr><td><code>429</code></td><td>Over {{ config('urlxo.api.per_minute') }} requests per minute for this key's account.</td></tr>
            </table>
            <pre><code>{
  "message": "This alias is already taken.",
  "errors": { "code": ["This alias is already taken."] }
}</code></pre>
        </article>
    </div>
</x-layouts.marketing>
