<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Enums\WalletType;
use App\Models\Wallet;

final readonly class SetupDefaultWallets
{
    private const DEFAULTS = [
        ['Bank', WalletType::Bank, 'landmark', '#2563eb'],
        ['Mobile Wallet', WalletType::MobileBanking, 'smartphone', '#7c3aed'],
        ['Card', WalletType::Card, 'credit-card', '#e11d48'],
        ['Cash', WalletType::Cash, 'banknote', '#16a34a'],
    ];

    public function __construct(private CreateWallet $createWallet) {}

    public function handle(): void
    {
        if (Wallet::query()->exists()) {
            return;
        }

        foreach (self::DEFAULTS as [$name, $type, $icon, $color]) {
            $this->createWallet->handle($name, $type, icon: $icon, color: $color);
        }
    }
}
