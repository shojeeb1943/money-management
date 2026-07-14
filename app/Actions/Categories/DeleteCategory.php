<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use RuntimeException;

final class DeleteCategory
{
    public function handle(Category $category): void
    {
        throw_if($category->hasTransactions(), RuntimeException::class, 'A category with transactions cannot be deleted. Archive it instead.');

        throw_if($category->children()->exists(), RuntimeException::class, 'A category with sub-categories cannot be deleted.');

        $category->delete();
    }
}
