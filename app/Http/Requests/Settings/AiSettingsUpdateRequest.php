<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AiSettingsUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $provider = (string) $this->input('provider');
        $isCustom = $provider === 'custom';
        $models = config("ai.providers.{$provider}.models", []);

        return [
            'provider' => ['required', Rule::in(array_keys(config('ai.providers')))],
            'model' => $isCustom
                ? ['required', 'string', 'max:100']
                : ['required', Rule::in($models)],
            'base_url' => [Rule::requiredIf($isCustom), 'nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
