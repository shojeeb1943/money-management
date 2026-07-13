<?php

namespace App\Actions\Categories;

use App\Models\Category;

class ArchiveCategory
{
    public function handle(Category $category): Category
    {
        $category->update(['archived_at' => $category->isArchived() ? null : now()]);

        return $category;
    }
}
