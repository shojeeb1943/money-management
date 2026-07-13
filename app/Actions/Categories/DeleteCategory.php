<?php

namespace App\Actions\Categories;

use App\Models\Category;
use RuntimeException;

class DeleteCategory
{
    public function handle(Category $category): void
    {
        if ($category->hasTransactions()) {
            throw new RuntimeException('A category with transactions cannot be deleted. Archive it instead.');
        }

        if ($category->children()->exists()) {
            throw new RuntimeException('A category with sub-categories cannot be deleted.');
        }

        $category->delete();
    }
}
