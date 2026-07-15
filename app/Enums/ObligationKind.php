<?php

declare(strict_types=1);

namespace App\Enums;

enum ObligationKind: string
{
    case Loan = 'loan';
    case Lend = 'lend';
    case Safekeeping = 'safekeeping';

    public function label(): string
    {
        return match ($this) {
            self::Loan => 'Loan',
            self::Lend => 'Lend',
            self::Safekeeping => 'Safekeeping',
        };
    }
}
