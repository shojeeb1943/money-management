<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

final class SaveCrossCompanyTransferRequest extends FormRequest
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
            'from_wallet_id' => ['required', 'different:to_wallet_id', 'exists:wallets,id'],
            'to_wallet_id' => ['required', 'exists:wallets,id'],
            'from_company_id' => ['required', 'different:to_company_id', 'exists:companies,id'],
            'to_company_id' => ['required', 'exists:companies,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
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
