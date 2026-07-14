<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Actions\Setup\EnsurePersonalAccessClient;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use phpseclib3\Crypt\RSA;
use RuntimeException;

final readonly class RunMigrations
{
    public function __construct(private EnsurePersonalAccessClient $ensurePersonalAccessClient) {}

    public function handle(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $this->ensurePassportKeys();

        $this->ensurePersonalAccessClient->handle();
    }

    private function ensurePassportKeys(): void
    {
        $privateKey = Passport::keyPath('oauth-private.key');
        $publicKey = Passport::keyPath('oauth-public.key');

        if (file_exists($privateKey) && file_exists($publicKey)) {
            return;
        }

        $key = RSA::createKey(4096);

        throw_if(file_put_contents($publicKey, (string) $key->getPublicKey()) === false
            || file_put_contents($privateKey, (string) $key) === false, RuntimeException::class, sprintf('Unable to write Passport keys at %s. Check that the directory is writable.', $privateKey));

        if (! windows_os()) {
            chmod($publicKey, 0660);
            chmod($privateKey, 0600);
        }
    }
}
