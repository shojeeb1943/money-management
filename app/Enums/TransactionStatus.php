<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Posted = 'posted';
    case Voided = 'voided';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
