<?php

namespace App\Http\Controllers\AuthHub;

use App\Http\Controllers\Controller;
use App\Models\AuthSite;
use Illuminate\Http\Request;

/**
 * Site registry API for provisioning scripts and MCP tools.
 * Bearer AUTH_HUB_ADMIN_TOKEN, served on the hub host under /api/hub/sites.
 */
class SiteRegistryController extends Controller
{
    public function index()
    {
        return ['data' => AuthSite::orderBy('slug')->get()->map->toRegistryArray()];
    }

    public function show(string $slug)
    {
        return ['data' => $this->find($slug)->toRegistryArray()];
    }

    public function store(Request $request)
    {
        $data = $request->validate(AuthSite::rules());
        $site = AuthSite::create($data + ['secret' => AuthSite::generateSecret()]);

        return response()->json(['data' => $site->toRegistryArray(withSecret: true)], 201);
    }

    public function update(Request $request, string $slug)
    {
        $site = $this->find($slug);
        $site->update($request->validate(AuthSite::rules($site)));

        return ['data' => $site->toRegistryArray()];
    }

    public function rotate(string $slug)
    {
        $site = $this->find($slug);
        $site->update(['secret' => AuthSite::generateSecret()]);

        return ['data' => $site->toRegistryArray(withSecret: true)];
    }

    public function destroy(string $slug)
    {
        $this->find($slug)->delete();

        return response()->noContent();
    }

    private function find(string $slug): AuthSite
    {
        return AuthSite::where('slug', $slug)->firstOrFail();
    }
}
