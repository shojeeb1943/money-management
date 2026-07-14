<?php

declare(strict_types=1);

namespace App\Support;

final class InstallationState
{
    public function installed(): bool
    {
        return file_exists($this->path());
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
}
