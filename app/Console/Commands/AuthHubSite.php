<?php

namespace App\Console\Commands;

use App\Models\AuthSite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class AuthHubSite extends Command
{
    protected $signature = 'authhub:site
        {action : list, add, update, rotate, enable, disable or remove}
        {slug? : Site id, e.g. onlycasting}
        {--url= : Consume URL on the site, e.g. https://example.com/auth/hub}
        {--name= : Display name}
        {--signed : Accept consume URLs the site signs itself (sites on many rotating domains)}
        {--unsigned : Only use the registered consume URL}';

    protected $description = 'Manage sites that sign in through the hub';

    public function handle(): int
    {
        $action = $this->argument('action');
        $slug = (string) $this->argument('slug');

        if ($action === 'list') {
            $this->table(['slug', 'name', 'consume url', 'signed', 'active', 'logins', 'last login'], AuthSite::orderBy('slug')->get()->map(fn (AuthSite $s) => [
                $s->slug, $s->name, $s->consume_url, $s->signed_consume ? 'yes' : 'no', $s->active ? 'yes' : 'no', $s->logins_count, $s->last_login_at?->diffForHumans() ?? '-',
            ]));

            return self::SUCCESS;
        }

        if ($slug === '') {
            $this->error('A site slug is required.');

            return self::FAILURE;
        }

        if ($action === 'add') {
            $data = ['slug' => $slug, 'name' => $this->option('name') ?: $slug, 'consume_url' => (string) $this->option('url'), 'signed_consume' => (bool) $this->option('signed')];
            if (! $this->valid($data, AuthSite::rules())) {
                return self::FAILURE;
            }
            $site = AuthSite::create($data + ['secret' => AuthSite::generateSecret()]);
            $this->printSecret($site);

            return self::SUCCESS;
        }

        $site = AuthSite::where('slug', $slug)->first();
        if (! $site) {
            $this->error("No site named {$slug}.");

            return self::FAILURE;
        }

        switch ($action) {
            case 'update':
                $data = array_filter(['name' => $this->option('name'), 'consume_url' => $this->option('url')]);
                if ($this->option('signed') || $this->option('unsigned')) {
                    $data['signed_consume'] = (bool) $this->option('signed');
                }
                if (! $data || ! $this->valid($data, AuthSite::rules($site))) {
                    $data || $this->error('Pass --url, --name, --signed or --unsigned.');

                    return self::FAILURE;
                }
                $site->update($data);
                $this->info("Updated {$slug}.");

                return self::SUCCESS;
            case 'rotate':
                $site->update(['secret' => AuthSite::generateSecret()]);
                $this->printSecret($site);

                return self::SUCCESS;
            case 'enable':
            case 'disable':
                $site->update(['active' => $action === 'enable']);
                $this->info(ucfirst($action)."d {$slug}.");

                return self::SUCCESS;
            case 'remove':
                $site->delete();
                $this->info("Removed {$slug}.");

                return self::SUCCESS;
        }

        $this->error("Unknown action {$action}.");

        return self::FAILURE;
    }

    private function valid(array $data, array $rules): bool
    {
        $validator = Validator::make($data, $rules);
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return $validator->passes();
    }

    private function printSecret(AuthSite $site): void
    {
        $this->info("Site {$site->slug} is ready. Put these in the site's .env:");
        $this->line('AUTH_HUB_URL=https://'.config('authhub.domain'));
        $this->line("AUTH_HUB_SITE={$site->slug}");
        $this->line("AUTH_HUB_SECRET={$site->secret}");
    }
}
