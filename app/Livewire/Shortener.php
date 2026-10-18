<?php

namespace App\Livewire;

use App\Services\LinkService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/** The live form in the landing page hero. Works for guests and signed-in users. */
class Shortener extends Component
{
    public string $url = '';

    public string $website = ''; // honeypot, real people never see it

    public ?string $shortUrl = null;

    public ?string $code = null;

    public ?string $destination = null;

    public function shorten(LinkService $links): void
    {
        $this->validate(['url' => ['required', 'string', 'max:2048']], ['url.required' => 'Paste a link to shorten.']);

        $user = auth()->user();
        if (! $user && ! config('urlxo.guest.enabled')) {
            throw ValidationException::withMessages(['url' => 'Create a free account to shorten links.']);
        }
        if ($this->website !== '') {
            return;
        }

        $key = 'shorten:'.($user ? 'u'.$user->id : request()->ip());
        $max = $user ? 300 : config('urlxo.guest.per_hour');
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages(['url' => 'You have made a lot of links in the last hour. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.']);
        }

        $link = $links->create(['url' => $this->url], $user, request()->ip());
        RateLimiter::hit($key, 3600);

        $this->code = $link->code;
        $this->shortUrl = $link->shortUrl();
        $this->destination = $link->url;
        $this->url = '';
    }

    public function again(): void
    {
        $this->reset(['shortUrl', 'code', 'destination']);
    }

    public function render()
    {
        return view('livewire.shortener');
    }
}
