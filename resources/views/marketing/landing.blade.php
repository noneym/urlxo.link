@php
    // Sample series for the analytics tile. Labelled as sample on the page.
    $sample = [12, 18, 15, 22, 31, 27, 19, 24, 38, 46, 41, 33, 29, 35, 52, 61, 48, 44, 57, 69, 63, 58, 71, 84];
    $sampleMax = max($sample);
    $domain = config('urlxo.short_domain');
@endphp
<x-layouts.marketing>
    {{-- Hero: asymmetric split, copy left, the real shortener right --}}
    <section class="wrap grid items-center gap-10 pb-16 pt-12 md:pt-20 lg:grid-cols-[1.2fr_1fr] lg:gap-14 lg:pb-24">
        <div>
            <h1 class="rise text-[42px] leading-[1.02] sm:text-[52px] lg:text-[56px]" style="--i:0">Short links that show who clicked.</h1>
            <p class="rise mt-5 max-w-[46ch] text-[17px] leading-relaxed text-ink-soft" style="--i:1">Shorten any URL on {{ $domain }}, see clicks by country, device and referrer, and automate it with a REST API.</p>
            <div class="rise mt-8 flex flex-wrap gap-3" style="--i:2">
                @auth
                    <a href="{{ route('links.index') }}" class="btn-ink">Open dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn-ink">Create free account</a>
                @endauth
                <a href="{{ route('docs') }}" class="btn-ghost">Read the API docs</a>
            </div>
        </div>
        <div class="rise" style="--i:2">
            <livewire:shortener />
        </div>
    </section>

    {{-- Features: bento with five cells for five features --}}
    <section id="features" class="wrap scroll-mt-24 py-16 lg:py-24">
        <h2 class="reveal max-w-[22ch] text-[34px] leading-[1.05] sm:text-[44px]">Everything a link needs after you share it.</h2>

        <div class="mt-10 grid gap-4 md:grid-cols-6 md:grid-rows-[auto_auto]">
            <article class="reveal panel relative overflow-hidden p-6 md:col-span-4 md:row-span-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-[22px]">Click analytics</h3>
                        <p class="mt-2 max-w-[44ch] text-[15px] text-muted">Daily clicks, unique visitors, countries, referrers, devices and browsers. Bots and chat previews are filtered out.</p>
                    </div>
                    <span class="chip shrink-0">Sample data</span>
                </div>
                <div class="mt-8 flex h-32 items-end gap-[3px]" aria-hidden="true">
                    @foreach ($sample as $i => $v)
                        <div class="flex-1 rounded-t-[3px] {{ $i >= count($sample) - 7 ? 'bg-accent' : 'bg-line-strong' }}" style="height: {{ round($v / $sampleMax * 100) }}%"></div>
                    @endforeach
                </div>
                <div class="mt-5 grid grid-cols-3 gap-4 text-[13px]">
                    <div><p class="text-muted">Top country</p><p class="mt-0.5 font-medium">Netherlands</p></div>
                    <div><p class="text-muted">Top referrer</p><p class="mt-0.5 font-medium">t.co</p></div>
                    <div><p class="text-muted">Mobile share</p><p class="mono mt-0.5 font-medium">63.4%</p></div>
                </div>
            </article>

            <article class="reveal relative overflow-hidden rounded-box bg-accent p-6 text-accent-ink md:col-span-2">
                <h3 class="text-[22px]">Your own alias</h3>
                <p class="mt-2 text-[15px] opacity-80">Pick the words after the slash, or let us generate a short code with no look-alike characters.</p>
                <p class="mono mt-8 break-all text-[20px] font-medium leading-snug">{{ $domain }}/<span class="underline decoration-2 underline-offset-4">spring-sale</span></p>
            </article>

            <article class="reveal panel grid grid-cols-[1fr_auto] items-center gap-5 p-6 md:col-span-2">
                <div>
                    <h3 class="text-[22px]">QR codes</h3>
                    <p class="mt-2 text-[15px] text-muted">Every link gets a crisp SVG QR code, ready for print.</p>
                </div>
                <div class="h-24 w-24 rounded-xl bg-white p-1 text-[#111314] [&_svg]:h-full [&_svg]:w-full">{!! \App\Support\Qr::svg('https://'.$domain) !!}</div>
            </article>

            <article class="reveal panel p-6 md:col-span-2">
                <h3 class="text-[22px]">Rules per link</h3>
                <ul class="mt-4 space-y-3 text-[15px] text-ink-soft">
                    <li class="flex items-center gap-3">@svg('phosphor-lock-key', 'h-5 w-5 text-accent') Password protection</li>
                    <li class="flex items-center gap-3">@svg('phosphor-calendar-x', 'h-5 w-5 text-accent') Expiry date and time</li>
                    <li class="flex items-center gap-3">@svg('phosphor-hand-palm', 'h-5 w-5 text-accent') Click limit</li>
                    <li class="flex items-center gap-3">@svg('phosphor-pause-circle', 'h-5 w-5 text-accent') Pause and resume</li>
                </ul>
            </article>

            <article class="reveal relative overflow-hidden rounded-box bg-ink p-6 text-bg md:col-span-2">
                <h3 class="text-[22px]">Fast redirects</h3>
                <p class="mt-2 text-[15px] opacity-75">Links resolve from Redis and clicks are written in the background, so visitors never wait on the database.</p>
                <p class="mono mt-6 text-[13px] opacity-60">302 Found</p>
            </article>
        </div>
    </section>

    {{-- API: copy left, real request and response right --}}
    <section id="api" class="scroll-mt-24 border-y border-line bg-surface">
        <div class="wrap grid gap-10 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:py-24">
            <div class="reveal">
                <p class="text-[12px] font-medium uppercase tracking-[0.16em] text-accent">Developer API</p>
                <h2 class="mt-3 text-[34px] leading-[1.05] sm:text-[44px]">Create links from code.</h2>
                <p class="mt-4 max-w-[48ch] text-[16px] leading-relaxed text-ink-soft">A small JSON API with Bearer keys. Create, update and delete links, then pull the same stats you see in the dashboard.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('docs') }}" class="btn-ink">Read the API docs</a>
                </div>
            </div>
            <div class="reveal min-w-0 overflow-hidden rounded-box border border-line bg-raised shadow-soft">
                <pre class="mono overflow-x-auto p-5 text-[13px] leading-relaxed text-ink-soft"><span class="text-muted"># create a link</span>
curl -X POST {{ url('/api/v1/links') }} \
  -H "Authorization: Bearer $URLXO_KEY" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com/launch","code":"launch"}'</pre>
                <pre class="mono overflow-x-auto border-t border-line p-5 text-[13px] leading-relaxed text-ink-soft"><span class="text-muted">201 Created</span>
{
  "data": {
    "code": "launch",
    "short_url": "<span class="text-accent">https://{{ $domain }}/launch</span>",
    "url": "https://example.com/launch",
    "clicks": 0,
    "is_active": true
  }
}</pre>
            </div>
        </div>
    </section>

    {{-- Use in the real world: full-width photo, copy stacked below --}}
    <section class="wrap py-16 lg:py-24">
        <div class="reveal overflow-hidden rounded-box">
            <img src="https://picsum.photos/id/180/1600/700" alt="Laptop, notebook and camera on a wooden desk" class="aspect-[16/9] w-full object-cover sm:aspect-[16/7]" width="1600" height="700" loading="lazy">
        </div>
        <div class="reveal mt-10 grid gap-8 md:grid-cols-[1fr_1.4fr]">
            <h2 class="text-[34px] leading-[1.05] sm:text-[40px]">One link for print, chat and email.</h2>
            <dl class="grid gap-6 sm:grid-cols-2">
                <div>
                    <dt class="font-medium">Change the destination later</dt>
                    <dd class="mt-1 text-[15px] text-muted">The poster is printed, the link keeps working. Point it somewhere new from the dashboard.</dd>
                </div>
                <div>
                    <dt class="font-medium">See which channel worked</dt>
                    <dd class="mt-1 text-[15px] text-muted">Make one link per channel and compare clicks side by side.</dd>
                </div>
                <div>
                    <dt class="font-medium">Case-sensitive codes</dt>
                    <dd class="mt-1 text-[15px] text-muted">More short codes fit in fewer characters, so links stay short.</dd>
                </div>
                <div>
                    <dt class="font-medium">No IP addresses stored</dt>
                    <dd class="mt-1 text-[15px] text-muted">Unique visitors are counted with a hash that changes every day.</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- FAQ: native disclosure, two columns on desktop --}}
    <section id="faq" class="wrap scroll-mt-24 py-16 lg:py-20">
        <h2 class="reveal text-[34px] leading-[1.05] sm:text-[40px]">Questions</h2>
        @php
            $faq = [
                ['Is urlxo free?', 'Yes. Shortening, analytics, QR codes and the API are free. Anonymous links have an hourly limit to keep spam out.'],
                ['Do I need an account?', 'Not to shorten a link. You need one to edit links, see click stats and create API keys.'],
                ['Can I change where a link goes?', 'Yes. Edit the destination, alias, password, expiry or click limit at any time. Changes apply right away.'],
                ['Which redirect status do you use?', 'A 302, so browsers do not cache the destination and every click is counted.'],
                ['Are links scanned for abuse?', 'Links to blocked domains are refused. Report a harmful link to abuse@urlxo.link and it will be removed.'],
                ['Where is my data stored?', 'In the EU. We keep the country, device, browser and referrer of a click, never the full IP address.'],
            ];
        @endphp
        <div class="mt-8 grid gap-x-10 md:grid-cols-2">
            @foreach ($faq as [$q, $a])
                <details class="reveal group border-b border-line py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[16px] font-medium">
                        {{ $q }}
                        <span class="text-muted transition group-open:rotate-45">@svg('phosphor-plus', 'h-4 w-4')</span>
                    </summary>
                    <p class="mt-3 max-w-[60ch] text-[15px] leading-relaxed text-muted">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Closing band --}}
    <section class="wrap pt-8">
        <div class="reveal flex flex-col items-start justify-between gap-6 rounded-box bg-ink px-6 py-10 text-bg sm:px-10 md:flex-row md:items-center">
            <h2 class="max-w-[24ch] text-[30px] leading-[1.08] sm:text-[36px]">Your next link can tell you more.</h2>
            @auth
                <a href="{{ route('links.index') }}" class="btn-accent">Open dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn-accent">Create free account</a>
            @endauth
        </div>
    </section>
</x-layouts.marketing>
