<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $obligation_id
 * @property int $wallet_id
 * @property int $amount
 * @property string $direction
 * @property Carbon $date
 * @property string|null $description
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Obligation $obligation
 * @property-read Wallet $wallet
 */
#[Fillable(['obligation_id', 'wallet_id', 'amount', 'direction', 'date', 'description', 'created_by'])]
final class ObligationPayment extends Model
{
    /**
     * @return BelongsTo<Obligation, $this>
     */
    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date',
        ];
    }
}
