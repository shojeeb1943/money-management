<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Recurring\ProcessRecurringTransactions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Create transactions for all recurring schedules that are due')]
#[Signature('moneta:process-recurring')]
final class ProcessRecurring extends Command
{
    public function handle(ProcessRecurringTransactions $processor): int
    {
        $count = $processor->handle();

        $this->info(sprintf('Processed %d recurring transaction(s).', $count));

        return self::SUCCESS;
    }
}
