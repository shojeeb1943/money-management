<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObligationKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ObligationKind $kind
 * @property string $label
 * @property int $wallet_id
 * @property int $amount
 * @property int $remaining
 * @property string $currency
 * @property string|null $description
 * @property string $status
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wallet $wallet
 * @property-read Collection<int, ObligationPayment> $payments
 */
#[Fillable(['kind', 'label', 'wallet_id', 'amount', 'remaining', 'currency', 'description', 'status', 'archived_at'])]
final class Obligation extends Model
{
    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return HasMany<ObligationPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ObligationPayment::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function isSettled(): bool
    {
        return $this->status === 'settled';
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ObligationKind::class,
            'amount' => 'integer',
            'remaining' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
