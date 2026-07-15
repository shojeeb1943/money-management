<?php

declare(strict_types=1);

namespace App\Actions\Wallets;

use App\Enums\WalletType;
use App\Models\User;
use App\Models\Wallet;

final class CreateWallet
{
    public function handle(
        string $name,
        WalletType $type,
        ?string $accountNumber = null,
        ?string $icon = null,
        ?string $color = null,
        int $openingBalance = 0,
        ?User $creator = null,
        ?string $currency = 'BDT',
    ): Wallet {
        return Wallet::query()->create([
            'name' => $name,
            'type' => $type,
            'account_number' => $accountNumber,
            'icon' => $icon,
            'color' => $color,
            'currency' => $currency,
            'opening_balance' => $openingBalance,
            'cached_balance' => $openingBalance,
        ]);
    }
}
