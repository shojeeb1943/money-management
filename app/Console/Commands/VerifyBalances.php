<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Verify every wallet cached balance matches the balance derived from its transactions')]
#[Signature('moneta:verify-balances')]
final class VerifyBalances extends Command
{
    public function handle(): int
    {
        $failures = 0;

        foreach (Wallet::query()->cursor() as $wallet) {
            $derived = $wallet->derivedBalance();

            if ($wallet->cached_balance !== $derived) {
                $this->error(sprintf('Wallet %d (%s) cached balance %d != derived %d.', $wallet->id, $wallet->name, $wallet->cached_balance, $derived));
                $failures++;
            }
        }

        if ($failures > 0) {
            $this->error(sprintf('Balance verification failed with %d issue(s).', $failures));

            return self::FAILURE;
        }

        $this->info('Balances verified: every wallet reconciles with its transaction history.');

        return self::SUCCESS;
    }
}
