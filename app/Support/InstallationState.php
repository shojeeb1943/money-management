<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class InstallationState
{
    public function installed(): bool
    {
        if (file_exists($this->path())) {
            return true;
        }

        if ($this->hasExistingAdmin()) {
            $this->markInstalled();

            return true;
        }

        return false;
    }

    public function markInstalled(): void
    {
        file_put_contents($this->path(), json_encode([
            'installed_at' => now()->toIso8601String(),
        ]));
    }

    public function path(): string
    {
        return storage_path('installed');
    }

    private function hasExistingAdmin(): bool
    {
        if (! $this->databaseReachable()) {
            return false;
        }

        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function databaseReachable(): bool
    {
        $config = config('database.connections.'.config('database.default'));

        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;

        if (! $host || ! $port || ! function_exists('fsockopen')) {
            return true;
        }

        $socket = @fsockopen($host, (int) $port, $errorCode, $errorMessage, 2);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
