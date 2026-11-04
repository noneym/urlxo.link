<?php

namespace App\Http\Controllers\AuthHub;

use App\Http\Controllers\Controller;
use App\Models\AuthSite;
use App\Support\AuthHub\Jwt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A built-in "site" for checking the hub end to end. Register a site with slug
 * `selftest` and consume_url https://<hub>/selftest/consume, set AUTH_HUB_SELFTEST=true,
 * then open /selftest. It does exactly what a real site does and shows the result.
 */
class SelfTestController extends Controller
{
    public function start(Request $request)
    {
        $this->site();

        $nonce = Str::random(40);
        $request->session()->put('selftest_nonce', $nonce);

        return redirect()->route('authhub.start', ['site' => 'selftest', 'nonce' => $nonce, 'return' => '/selftest']);
    }

    public function consume(Request $request)
    {
        $site = $this->site();
        $nonce = (string) $request->session()->pull('selftest_nonce', '');

        if ($request->filled('error')) {
            return view('authhub.selftest', ['error' => 'Google returned: '.$request->query('error'), 'claims' => null, 'checks' => []]);
        }

        $claims = Jwt::decode((string) $request->query('token', ''), $site->secret);
        $checks = [
            'signature' => $claims !== null,
            'audience' => ($claims['aud'] ?? null) === 'selftest',
            'not expired' => ($claims['exp'] ?? 0) >= time(),
            'nonce matches session' => $nonce !== '' && hash_equals($nonce, (string) ($claims['nonce'] ?? '')),
        ];

        return view('authhub.selftest', ['error' => null, 'claims' => $claims, 'checks' => $checks]);
    }

    private function site(): AuthSite
    {
        abort_unless(config('authhub.selftest'), 404);

        return AuthSite::where('slug', 'selftest')->where('active', true)->firstOrFail();
    }
}
