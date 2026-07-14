<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;

final class CompanyPolicy
{
    public function delete(): bool
    {
        return Company::query()->count() > 1;
    }
}
