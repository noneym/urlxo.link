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
