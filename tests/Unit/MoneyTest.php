<?php

declare(strict_types=1);

use App\Support\Money;

test('money formatting omits zero decimal places', function (): void {
    expect(Money::format(428_000_00))->toBe('৳428,000')
        ->and(Money::format(0))->toBe('৳0')
        ->and(Money::format(-150_000))->toBe('-৳1,500')
        ->and(Money::format(150_050))->toBe('৳1,500.50');
});
