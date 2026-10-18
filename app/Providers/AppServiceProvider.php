<?php

namespace App\Providers;

use App\Mail\Transport\CloudflareEmailTransport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('urlxo.api.per_minute'))->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json(['message' => 'Too many requests. Slow down and retry shortly.'], 429));
        });

        Mail::extend('cloudflare', function (array $config) {
            return new CloudflareEmailTransport(
                accountId: (string) ($config['account_id'] ?? ''),
                token: (string) ($config['token'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://api.cloudflare.com/client/v4'),
                timeout: (int) ($config['timeout'] ?? 20),
            );
        });
    }
}
