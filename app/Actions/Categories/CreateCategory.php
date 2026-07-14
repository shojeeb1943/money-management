<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Company;
use InvalidArgumentException;

final class CreateCategory
{
    public function handle(
        Company $company,
        string $name,
        CategoryKind $kind,
        ?Category $parent = null,
        ?string $icon = null,
        ?string $color = null,
    ): Category {
        if ($parent instanceof Category) {
            $this->assertValidParent($company, $parent, $kind);
        }

        return Category::query()->create([
            'company_id' => $company->id,
            'parent_id' => $parent?->id,
            'kind' => $kind,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    private function assertValidParent(Company $company, Category $parent, CategoryKind $kind): void
    {
        throw_if($parent->company_id !== $company->id, InvalidArgumentException::class, 'Parent category belongs to another company.');

        throw_if($parent->kind !== $kind, InvalidArgumentException::class, 'Parent category kind does not match.');

        throw_if($parent->parent_id !== null, InvalidArgumentException::class, 'Categories can only be nested one level deep.');
    }
}
