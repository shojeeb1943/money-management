<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use InvalidArgumentException;

final class CreateCategory
{
    public function handle(
        string $name,
        CategoryKind $kind,
        ?Category $parent = null,
        ?string $icon = null,
        ?string $color = null,
    ): Category {
        if ($parent instanceof Category) {
            $this->assertValidParent($parent, $kind);
        }

        return Category::query()->create([
            'parent_id' => $parent?->id,
            'kind' => $kind,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
        ]);
    }

    private function assertValidParent(Category $parent, CategoryKind $kind): void
    {
        throw_if($parent->kind !== $kind, InvalidArgumentException::class, 'Parent category kind does not match.');

        throw_if($parent->parent_id !== null, InvalidArgumentException::class, 'Categories can only be nested one level deep.');
    }
}
