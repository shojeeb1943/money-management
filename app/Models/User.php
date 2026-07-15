<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasCompanies;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_company_id
 * @property string|null $ai_provider
 * @property string|null $ai_model
 * @property string|null $ai_api_key
 * @property string|null $ai_base_url
 * @property string|null $ai_fallback_provider
 * @property string|null $ai_fallback_model
 * @property string|null $ai_fallback_api_key
 * @property string|null $ai_fallback_base_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company|null $currentCompany
 * @property-read Collection<int, Company> $ownedCompanies
 * @property-read Collection<int, Company> $companies
 */
#[Fillable(['name', 'email', 'password', 'current_company_id', 'ai_provider', 'ai_model', 'ai_api_key', 'ai_base_url', 'ai_fallback_provider', 'ai_fallback_model', 'ai_fallback_api_key', 'ai_fallback_base_url'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'ai_api_key', 'ai_fallback_api_key'])]
final class User extends Authenticatable implements OAuthenticatable, PasskeyUser
{
    use HasApiTokens;
    use HasCompanies;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'ai_api_key' => 'encrypted',
            'ai_fallback_api_key' => 'encrypted',
        ];
    }
}
