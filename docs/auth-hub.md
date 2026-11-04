# Sign-in hub (auth.urlxo.link)

One Google OAuth client with one fixed redirect URI (`https://auth.urlxo.link/callback`)
gives "Sign in with Google" to any number of sites. Sites are rows in `auth_sites`, so
adding a site or moving it to a new domain never touches Google.

```
site  /login/google  ──►  auth.urlxo.link/start?site=<slug>&nonce=<n>&return=/path
                              │  (signed state + browser cookie)
                              ▼
                          accounts.google.com  (openid email profile)
                              │
                              ▼
      auth.urlxo.link/callback  ──►  <consume_url>?token=<HS256 JWT>
                                         (or ?error=access_denied&return=/path)
```

The hub stores no user data. It only passes the Google profile to the site in a token
that is signed with the site's secret and expires in 120 seconds.

## Hub setup (once)

1. Google Cloud Console → Google Auth Platform → **External**.
   - App name: `urlxo`; home page `https://urlxo.link`; privacy `https://urlxo.link/legal/privacy`;
     terms `https://urlxo.link/legal/terms`; authorized domain `urlxo.link`.
   - Scopes: `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile` (no sensitive scopes).
2. Clients → **Web application** → authorized redirect URI `https://auth.urlxo.link/callback`.
   No JavaScript origins needed (redirect flow only).
3. Env on the urlxo service:

   | var | value |
   |---|---|
   | `AUTH_HUB_DOMAIN` | `auth.urlxo.link` |
   | `AUTH_HUB_GOOGLE_CLIENT_ID` | web client id |
   | `AUTH_HUB_GOOGLE_CLIENT_SECRET` | web client secret |
   | `AUTH_HUB_ADMIN_TOKEN` | 24+ random chars, enables the registry API |

4. Test in "Testing" mode with your own account, then **Publish app** and submit branding verification.

## Registering a site

```bash
php artisan authhub:site add onlycasting --url=https://example.com/auth/hub --name="OnlyCasting"
php artisan authhub:site list
php artisan authhub:site update onlycasting --url=https://new-domain.com/auth/hub
php artisan authhub:site rotate onlycasting     # new secret
php artisan authhub:site disable onlycasting    # or enable / remove
```

Or over HTTP on the hub host (bearer `AUTH_HUB_ADMIN_TOKEN`):

| method | path | body |
|---|---|---|
| GET | `/api/hub/sites` | |
| POST | `/api/hub/sites` | `slug`, `name`, `consume_url` → returns `secret` once |
| GET | `/api/hub/sites/{slug}` | |
| PATCH | `/api/hub/sites/{slug}` | any of `name`, `consume_url`, `active` |
| POST | `/api/hub/sites/{slug}/rotate` | → returns new `secret` |
| DELETE | `/api/hub/sites/{slug}` | |

`consume_url` must be https (http only for localhost / `.test`).

## Sites on many (rotating) domains

One codebase and database behind many domains registers once with
`--signed` (`signed_consume: true`). On each sign-in the site sends the consume
URL on the domain the user is on, signed with its secret:

```
/start?site=<slug>&nonce=<n>&return=/path&consume=https://<current-host>/auth/hub&ts=<unix>&sig=<sig>
sig = base64url(HMAC-SHA256(secret, "<slug>\n<nonce>\n<return>\n<consume>\n<ts>"))
```

The hub accepts it only when the signature matches, `ts` is within 5 minutes,
and the URL is https with the same path as the registered `consume_url` and no
query. Without the signature anyone could have the token for a user sent to
their own domain. The site should only sign hosts it actually serves.

## Site side

The site needs `AUTH_HUB_URL`, `AUTH_HUB_SITE` and `AUTH_HUB_SECRET`. Two routes:

1. **Start**: make a random nonce, keep it in the session, redirect to the hub.
2. **Consume**: verify the token (signature, `aud`, `exp`, `nonce` equals the session nonce,
   `jti` not seen before), then find or create the user by `sub` and sign them in.

Token claims: `iss`, `aud` (site slug), `sub` (Google account id, stable), `email`,
`email_verified`, `name`, `given_name`, `family_name`, `picture`, `locale`, `nonce`,
`return` (local path to send the user to), `iat`, `exp`, `jti`.

Key users by `sub`, not by e-mail. Only link to an existing e-mail account when
`email_verified` is true.

### Laravel drop-in

`config/services.php`:

```php
'authhub' => [
    'url' => env('AUTH_HUB_URL', 'https://auth.urlxo.link'),
    'site' => env('AUTH_HUB_SITE'),
    'secret' => env('AUTH_HUB_SECRET'),
],
```

`routes/web.php`:

```php
Route::get('/login/google', [HubLoginController::class, 'start'])->name('login.google');
Route::get('/auth/hub', [HubLoginController::class, 'consume']);
```

`app/Http/Controllers/HubLoginController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HubLoginController extends Controller
{
    public function start(Request $request)
    {
        $nonce = Str::random(40);
        $request->session()->put('hub_nonce', $nonce);

        $return = $request->query('return', '/');

        return redirect()->away(config('services.authhub.url').'/start?'.http_build_query([
            'site' => config('services.authhub.site'),
            'nonce' => $nonce,
            'return' => str_starts_with($return, '/') && ! str_starts_with($return, '//') ? $return : '/',
        ]));
    }

    public function consume(Request $request)
    {
        if ($request->filled('error')) {
            return redirect('/login')->with('error', 'Google sign-in was cancelled.');
        }

        $claims = $this->verify((string) $request->query('token'));
        $nonce = $request->session()->pull('hub_nonce');

        abort_unless($claims && $nonce && hash_equals($nonce, (string) ($claims['nonce'] ?? '')), 403, 'Invalid sign-in.');
        // Single use: refuse a token that was already consumed.
        abort_unless(Cache::add('hub_jti:'.$claims['jti'], 1, 300), 403, 'Sign-in link already used.');

        $user = User::where('google_id', $claims['sub'])->first();
        if (! $user && ($claims['email_verified'] ?? false)) {
            $user = User::where('email', $claims['email'])->first();
            $user?->update(['google_id' => $claims['sub']]);
        }
        $user ??= User::create([
            'google_id' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'] ?? Str::before($claims['email'], '@'),
            'password' => bcrypt(Str::random(40)),
        ]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect($claims['return'] ?? '/');
    }

    private function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$h, $p, $s] = $parts;
        $b64 = fn ($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        if (! hash_equals($b64(hash_hmac('sha256', "$h.$p", config('services.authhub.secret'), true)), $s)) {
            return null;
        }
        $claims = json_decode(base64_decode(strtr($p, '-_', '+/')), true);

        return is_array($claims)
            && ($claims['aud'] ?? null) === config('services.authhub.site')
            && ($claims['exp'] ?? 0) >= time()
            && ! empty($claims['sub']) && ! empty($claims['jti'])
            ? $claims : null;
    }
}
```

Add a nullable unique `google_id` column to `users`.

## Current setup (as of 2026-11-04)

No secrets here: they live only in dockxo env and in the hub database.

### Google

| | |
|---|---|
| Google account | abuyousufsifan@gmail.com (also the Search Console owner of `sc-domain:urlxo.link`) |
| Cloud project | `urlxo-auth` (separate from the google-mcp project, so a suspension here does not hit it) |
| OAuth client | Web application `auth.urlxo.link`, client id `416408771125-vj88ohsi1b81mf1glse5efsko6cgrr84.apps.googleusercontent.com` |
| Redirect URI | `https://auth.urlxo.link/callback` (the only one) |
| Status | Published (In production), branding verified and published |
| Scopes | `openid email profile` only |

Rotating the client secret: Cloud Console → Clients → add secret, set
`AUTH_HUB_GOOGLE_CLIENT_SECRET` on dockxo `urlxo-link.urlxo-link`, redeploy, then
disable the old secret.

### Hub deployment

- dockxo service `urlxo-link.urlxo-link` (project urlxo.link), the same container as
  the URL shortener. Domain `auth.urlxo.link` is routed to **port 80**: `add_domain`
  defaults to the service port (3000) and that gives a 502.
- DNS: Cloudflare, `auth.urlxo.link` A → 5.9.104.212, proxied.
- Env: `AUTH_HUB_DOMAIN`, `AUTH_HUB_GOOGLE_CLIENT_ID`, `AUTH_HUB_GOOGLE_CLIENT_SECRET`,
  `AUTH_HUB_ADMIN_TOKEN` (registry API), `AUTH_HUB_SELFTEST=true`.
- Migrations run on container start (entrypoint), so hub schema changes deploy themselves.
- Health check end to end: open https://auth.urlxo.link/selftest (site `selftest`).

### Registered sites

| slug | code | deployments (dockxo) | database | consume |
|---|---|---|---|---|
| `selftest` | this repo | urlxo-link.urlxo-link | hub DB | `https://auth.urlxo.link/selftest/consume` |
| `bunnyipcam` | noneym/bunnyIPCam, branch `hiddenPay` | laravel.bunnyipcam | mariadb-bunnyipcam | fixed: `https://bunnyipcam.com/auth/hub` |
| `turkifsatube` | noneym/turkifsatube, `main` | laravel, laravel-2, laravel-subsite, laravel-subsite-2 (turkiftube) | mariadb-master-router-turkiftube | **signed**: any turkifsatube domain, path `/auth/hub` |
| `pezotavsan` | noneym/turkifsatube, `main` | laravel.pezotavsan | mariadb-pezotavsan | **signed**: any pezotavsan domain, path `/auth/hub` |
| `turkunlu` | noneym/bunnyIPCam, branch `turkunlu` | laravel.turkunlu | mariadb-turkunlu | **signed**: any turkunlu domain (primary turkunlu1.sbs), path `/auth/hub` |

Each site's deployments carry `AUTH_HUB_URL`, `AUTH_HUB_SITE` and `AUTH_HUB_SECRET`.
Deployments that share a database share one registration and secret.

Site code:
- bunnyIPCam: `app/Http/Controllers/Auth/GoogleLoginController.php` and
  `resources/views/components/google-login.blade.php`. `main` (youtubekatil) has the same
  code but no hub env, so the button is hidden there. The `turkunlu` branch has the
  same commits cherry-picked (the branches have diverged, never merge them) plus a
  signed start that signs the request's `HTTP_HOST`; the site's own domain gates
  (CheckReferer, RedirectToPrimaryDomain) have already accepted that host.
- turkifsatube: same file names. The start link signs the current host; when
  the domain restriction setting is on, only hosts in the allowed-domains list
  (`AllowedReferer`) are signed, so a new mirror domain works as soon as it is
  added there.

Behaviour on the sites:
- Users are matched on the Google id (`users.google_id`).
- An existing account is linked by e-mail only when Google reports the e-mail
  as verified. After that it is only signed in: name, VIP and other fields are
  untouched.
- New accounts get a random `user_xxxxxxxx` username, never the Google name,
  because usernames are public.
- On turkifsatube the one-session rule (`auth_token`) applies as with password login.
- The button stays hidden until `AUTH_HUB_SITE` and `AUTH_HUB_SECRET` are set and the
  `users.google_id` column exists (checked every 5 minutes).

### Adding another site

1. Register: `POST /api/hub/sites` (add `"signed_consume": true` for multi-domain codebases)
   or `php artisan authhub:site add <slug> --url=https://<domain>/auth/hub [--signed]`.
   Keep the name ASCII when sending it with curl from Windows: a non-ASCII name arrived
   as an empty body.
2. Set the three `AUTH_HUB_*` env vars on every deployment that uses that database.
3. Add the `google_id` column. bunnyIPCam and turkifsatube deploys do **not** run
   migrations, so run only that one:
   `php artisan migrate --force --path=database/migrations/2026_11_04_120000_add_google_id_to_users.php`
   with the deployment's `DB_*` values in the environment. pezotavsan's database has
   many old pending migrations that must not be run blindly.
4. Wait up to 5 minutes for the button, then try a real sign-in.
