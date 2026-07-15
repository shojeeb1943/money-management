<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\ObligationKind;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(ObligationKind::class)],
            'label' => ['required', 'string', 'max:100'],
            'wallet_id' => ['required', 'exists:wallets,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('amount') && is_numeric(str_replace([',', ' '], '', (string) $this->input('amount')))) {
            $this->merge(['amount' => Money::toMinorUnits((string) $this->input('amount'))]);
        }
    }
}
