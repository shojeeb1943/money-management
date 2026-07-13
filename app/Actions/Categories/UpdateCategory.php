<?php

namespace App\Actions\Categories;

use App\Models\Category;

class UpdateCategory
{
    public function handle(
        Category $category,
        string $name,
        ?string $icon = null,
        ?string $color = null,
    ): Category {
        $category->update([
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
        ]);

        return $category;
    }
}
