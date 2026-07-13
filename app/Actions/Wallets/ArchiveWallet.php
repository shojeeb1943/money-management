<?php

namespace App\Actions\Wallets;

use App\Models\Wallet;

class ArchiveWallet
{
    public function handle(Wallet $wallet): Wallet
    {
        $wallet->update(['archived_at' => $wallet->isArchived() ? null : now()]);

        return $wallet;
    }
}
