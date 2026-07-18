<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class CategoriesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, int>  $categoryIds
     */
    public function __construct(private readonly Collection $categoryIds) {}

    public function collection(): Collection
    {
        return Category::query()
            ->whereIn('id', $this->categoryIds)
            ->with('parent')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'kind' => $category->kind->value,
                'parent' => $category->parent?->name,
                'archived' => $category->isArchived() ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Kind', 'Parent', 'Archived'];
    }

    public function title(): string
    {
        return 'Categories';
    }
}
