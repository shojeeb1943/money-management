<?php

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Company;

class SetupDefaultCategories
{
    private const array BUSINESS_DEFAULTS = [
        CategoryKind::Income->value => [
            ['Sales', 'shopping-cart', '#16a34a'],
            ['Services', 'briefcase', '#0d9488'],
            ['Other Income', 'circle-plus', '#64748b'],
        ],
        CategoryKind::Expense->value => [
            ['Software & Hosting', 'server', '#dc2626'],
            ['Marketing', 'megaphone', '#ea580c'],
            ['Salaries', 'users', '#7c3aed'],
            ['Office Rent', 'building', '#2563eb'],
            ['Bank Charges', 'landmark', '#475569'],
            ['Other Expense', 'circle-minus', '#64748b'],
        ],
    ];

    private const array PERSONAL_DEFAULTS = [
        CategoryKind::Income->value => [
            ['Salary', 'wallet', '#16a34a'],
            ['Other Income', 'circle-plus', '#64748b'],
        ],
        CategoryKind::Expense->value => [
            ['Food', 'utensils', '#ea580c'],
            ['Rent', 'home', '#2563eb'],
            ['Transport', 'bus', '#0d9488'],
            ['Family', 'heart', '#db2777'],
            ['Utilities', 'plug', '#475569'],
            ['Donation', 'hand-heart', '#7c3aed'],
            ['Other Expense', 'circle-minus', '#64748b'],
        ],
    ];

    public function __construct(private CreateCategory $createCategory) {}

    public function handle(Company $company): void
    {
        $defaults = $company->is_personal ? self::PERSONAL_DEFAULTS : self::BUSINESS_DEFAULTS;

        foreach ($defaults as $kind => $categories) {
            foreach ($categories as [$name, $icon, $color]) {
                $this->createCategory->handle($company, $name, CategoryKind::from($kind), icon: $icon, color: $color);
            }
        }
    }
}
