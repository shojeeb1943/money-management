<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryKind;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property CategoryKind $kind
 * @property string $name
 * @property string|null $icon
 * @property string|null $color
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 */
#[Fillable(['parent_id', 'kind', 'name', 'icon', 'color', 'archived_at'])]
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function hasTransactions(): bool
    {
        return $this->transactions()->exists();
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('archived_at'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CategoryKind::class,
            'archived_at' => 'datetime',
        ];
    }
}
