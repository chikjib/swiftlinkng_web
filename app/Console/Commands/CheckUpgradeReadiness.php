<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Throwable;

class CheckUpgradeReadiness extends Command
{
    protected $signature = 'upgrade:check';
    protected $description = 'Check PHP 8.4, session storage, and existing Passport clients without modifying data';

    public function handle(): int
    {
        $failed = false;
        $checks = [
            'PHP 8.4 runtime' => fn () => PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4,
            'Application encryption key is configured' => fn () => filled(config('app.key')),
            'Production debug output is disabled' => fn () => ! config('app.debug'),
            'Database connection' => fn () => (bool) DB::connection()->getPdo(),
            'Writable storage and bootstrap cache' => fn () => is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')),
            'Legacy session serialization is preserved' => fn () => config('session.serialization') === 'php',
            'Persistent session storage is accessible' => function () {
                if (in_array(config('session.driver'), ['array', null], true)) {
                    return false;
                }
                if (config('session.driver') === 'file') {
                    return is_dir(config('session.files')) && is_writable(config('session.files'));
                }
                // Read an unused ID to verify backend connectivity without changing sessions.
                app('session')->driver()->getHandler()->read(str_repeat('0', 40));
                return true;
            },
            'Passport public key can validate tokens' => fn () => app(ResourceServer::class) instanceof ResourceServer,
            'An existing personal access client is available' => fn () => (bool) app(ClientRepository::class)
                ->personalAccessClient(config('auth.guards.api.provider')),
            'Existing OAuth client secrets are hashed' => function () {
                foreach (Passport::client()->newQuery()->whereNotNull('secret')->cursor() as $client) {
                    if (! Hash::isHashed($client->secret)) {
                        return false;
                    }
                }
                return true;
            },
        ];

        foreach ($checks as $name => $check) {
            try {
                $passed = (bool) $check();
            } catch (Throwable) {
                // Connection errors can contain credentials: report only the failed check.
                $passed = false;
            }
            $this->line(($passed ? 'PASS: ' : 'FAIL: ').$name);
            $failed = $failed || ! $passed;
        }

        if ($failed) {
            $this->error('Keep maintenance mode enabled and resolve failed checks before reopening the site.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
