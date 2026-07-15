<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Closure;
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

        $fallbackProvider = (string) $this->input('fallback_provider');
        $hasFallback = $fallbackProvider !== '';
        $isFallbackCustom = $fallbackProvider === 'custom';
        $fallbackModels = config("ai.providers.{$fallbackProvider}.models", []);

        return [
            'provider' => ['required', Rule::in(array_keys(config('ai.providers')))],
            'model' => $isCustom
                ? ['required', 'string', 'max:100']
                : ['required', Rule::in($models)],
            'base_url' => [Rule::requiredIf($isCustom), 'nullable', 'url', 'max:255', $this->notPrivateNetwork()],
            'api_key' => ['nullable', 'string', 'max:500'],

            'fallback_provider' => ['nullable', Rule::in(array_keys(config('ai.providers')))],
            'fallback_model' => $isFallbackCustom
                ? [Rule::requiredIf($hasFallback), 'nullable', 'string', 'max:100']
                : [Rule::requiredIf($hasFallback), 'nullable', Rule::in($fallbackModels)],
            'fallback_base_url' => [Rule::requiredIf($hasFallback && $isFallbackCustom), 'nullable', 'url', 'max:255', $this->notPrivateNetwork()],
            'fallback_api_key' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('fallback_provider') === '') {
            $this->merge(['fallback_provider' => null]);
        }
    }

    private function notPrivateNetwork(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $host = parse_url($value, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                return;
            }

            $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

            if ($ip === $host && ! filter_var($host, FILTER_VALIDATE_IP)) {
                return; // Could not resolve the host; don't block on a DNS lookup failure.
            }

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('The :attribute must not point to a private or reserved network address.');
            }
        };
    }
}
