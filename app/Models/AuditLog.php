<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed>|null $changes
 * @property Carbon $created_at
 * @property Carbon|null $restored_at
 * @property int|null $restored_by
 * @property-read Company $company
 * @property-read User|null $user
 */
#[Fillable(['company_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'created_at', 'restored_at', 'restored_by'])]
#[WithoutTimestamps]
final class AuditLog extends Model
{
    use BelongsToCompany;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }
}
