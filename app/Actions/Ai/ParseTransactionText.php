<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class ParseTransactionText
{
    /**
     * @return array{type: string, amount: float, date: string, description: ?string, walletId: ?int, counterWalletId: ?int, categoryId: ?int}
     */
    public function handle(User $user, Company $company, string $text): array
    {
        $wallets = Wallet::query()->active()->orderBy('name')->get(['id', 'name']);
        $categories = Category::query()->active()->orderBy('name')->get(['id', 'name', 'kind']);

        $prompt = $this->buildPrompt($wallets, $categories);

        try {
            $primary = $this->providerConfig($user, 'ai_provider', 'ai_model', 'ai_api_key', 'ai_base_url');
            throw_if($primary === null, RuntimeException::class, 'No AI provider configured. Set one up in Settings → AI.');

            $raw = $this->call($primary, $prompt, $text);
        } catch (Throwable $exception) {
            $fallback = $this->providerConfig($user, 'ai_fallback_provider', 'ai_fallback_model', 'ai_fallback_api_key', 'ai_fallback_base_url');
            throw_if($fallback === null, RuntimeException::class, $exception->getMessage());

            $raw = $this->call($fallback, $prompt, $text);
        }

        return $this->resolve($raw, $company, $wallets, $categories);
    }

    /**
     * @return array{provider: string, style: string, baseUrl: string, apiKey: string, model: string}|null
     */
    private function providerConfig(User $user, string $providerField, string $modelField, string $apiKeyField, string $baseUrlField): ?array
    {
        $provider = $user->{$providerField};

        if ($provider === null) {
            return null;
        }

        $config = config("ai.providers.{$provider}");
        throw_if($config === null, RuntimeException::class, "Unknown AI provider: {$provider}.");

        $apiKey = $user->{$apiKeyField};
        throw_if(blank($apiKey), RuntimeException::class, 'No AI API key configured. Set one up in Settings → AI.');

        $baseUrl = $provider === 'custom' ? $user->{$baseUrlField} : $config['base_url'];
        throw_if(blank($baseUrl), RuntimeException::class, 'No AI base URL configured. Set one up in Settings → AI.');

        $model = filled($user->{$modelField}) ? $user->{$modelField} : ($config['models'][0] ?? null);
        throw_if($model === null, RuntimeException::class, 'No AI model configured. Set one up in Settings → AI.');

        return ['provider' => $provider, 'style' => $config['style'], 'baseUrl' => $baseUrl, 'apiKey' => $apiKey, 'model' => $model];
    }

    /**
     * @param  array{provider: string, style: string, baseUrl: string, apiKey: string, model: string}  $config
     * @return array<string, mixed>
     */
    private function call(array $config, string $prompt, string $text): array
    {
        return $config['style'] === 'anthropic'
            ? $this->callAnthropic($config['baseUrl'], $config['apiKey'], $config['model'], $prompt, $text)
            : $this->callOpenAiCompatible($config['baseUrl'], $config['apiKey'], $config['model'], $prompt, $text, $config['provider'] !== 'custom');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Wallet>  $wallets
     * @param  \Illuminate\Database\Eloquent\Collection<int, Category>  $categories
     */
    private function buildPrompt($wallets, $categories): string
    {
        $walletNames = $wallets->pluck('name')->implode(', ');
        $categoryNames = $categories->map(fn (Category $category): string => sprintf('%s (%s)', $category->name, $category->kind->value))->implode(', ');

        return <<<PROMPT
            You extract a single financial transaction from a short note written by a small-business owner. The note may be in English, Bangla, or Banglish (romanized Bangla) - handle all three.

            Available wallets: {$walletNames}
            Available categories: {$categoryNames}

            Reply with ONLY a JSON object, no markdown fences, matching exactly:
            {"type": "income"|"expense"|"transfer", "amount": number, "date": "YYYY-MM-DD or null", "description": "short string or null", "wallet_name": "closest matching wallet name from the list, or null", "counter_wallet_name": "for transfers only, the destination wallet name from the list, or null", "category_name": "closest matching category name from the list, or null (omit for transfers)"}

            If no date is mentioned, use null (today will be assumed). Only use wallet/category names from the lists given.
            PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function callOpenAiCompatible(string $baseUrl, string $apiKey, string $model, string $prompt, string $text, bool $forceJsonMode): array
    {
        $body = [
            'model' => $model,
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $text],
            ],
        ];

        if ($forceJsonMode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withToken($apiKey)
            ->timeout(20)
            ->post(rtrim($baseUrl, '/').'/chat/completions', $body);

        if (! $response->successful()) {
            Log::warning('AI provider request failed.', ['status' => $response->status(), 'body' => $response->body()]);

            throw new RuntimeException('The AI provider request failed.');
        }

        $content = (string) $response->json('choices.0.message.content');

        return $this->decode($content);
    }

    /**
     * @return array<string, mixed>
     */
    private function callAnthropic(string $baseUrl, string $apiKey, string $model, string $prompt, string $text): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(20)
            ->post(rtrim($baseUrl, '/').'/messages', [
                'model' => $model,
                'max_tokens' => 512,
                'system' => $prompt,
                'messages' => [
                    ['role' => 'user', 'content' => $text],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('AI provider request failed.', ['status' => $response->status(), 'body' => $response->body()]);

            throw new RuntimeException('The AI provider request failed.');
        }

        // Claude models with adaptive thinking enabled (e.g. claude-sonnet-5) return a
        // "thinking" content block before the "text" block, so index 0 isn't reliable.
        $blocks = (array) $response->json('content', []);
        $textBlock = collect($blocks)->first(fn (array $block): bool => ($block['type'] ?? null) === 'text');
        throw_if($textBlock === null, RuntimeException::class, 'The AI provider returned no text content.');

        return $this->decode((string) ($textBlock['text'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        $cleaned = trim((string) preg_replace('/^```(?:json)?|```$/m', '', trim($content)));
        $decoded = json_decode($cleaned, true);

        throw_unless(is_array($decoded), RuntimeException::class, 'The AI provider returned an unparseable response.');

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  \Illuminate\Database\Eloquent\Collection<int, Wallet>  $wallets
     * @param  \Illuminate\Database\Eloquent\Collection<int, Category>  $categories
     * @return array{type: string, amount: float, date: string, description: ?string, walletId: ?int, counterWalletId: ?int, categoryId: ?int}
     */
    private function resolve(array $raw, Company $company, $wallets, $categories): array
    {
        $type = is_string($raw['type'] ?? null) ? Str::lower(trim($raw['type'])) : null;
        $type = in_array($type, ['income', 'expense', 'transfer'], true) ? $type : 'expense';

        $wallet = $this->findByName($wallets, $raw['wallet_name'] ?? null);
        $counterWallet = $type === 'transfer' ? $this->findByName($wallets, $raw['counter_wallet_name'] ?? null) : null;
        $category = $type !== 'transfer'
            ? $categories->first(fn (Category $category): bool => $category->kind->value === $type
                && $this->namesMatch($category->name, $raw['category_name'] ?? null))
            : null;

        $date = is_string($raw['date'] ?? null) && $raw['date'] !== '' && strtotime($raw['date']) !== false
            ? $raw['date']
            : now($company->timezone)->toDateString();

        return [
            'type' => $type,
            'amount' => $this->parseAmount($raw['amount'] ?? null),
            'date' => $date,
            'description' => is_string($raw['description'] ?? null) && $raw['description'] !== '' ? $raw['description'] : null,
            'walletId' => $wallet?->id,
            'counterWalletId' => $counterWallet?->id,
            'categoryId' => $category?->id,
        ];
    }

    private function parseAmount(mixed $raw): float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return 0.0;
        }

        try {
            return Money::toMinorUnits($raw) / 100;
        } catch (InvalidArgumentException) {
            return 0.0;
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Wallet>  $wallets
     */
    private function findByName($wallets, ?string $name): ?Wallet
    {
        return $wallets->first(fn (Wallet $wallet): bool => $this->namesMatch($wallet->name, $name));
    }

    private function namesMatch(string $candidate, ?string $target): bool
    {
        return $target !== null && Str::lower(trim($candidate)) === Str::lower(trim($target));
    }
}
