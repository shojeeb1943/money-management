<?php

declare(strict_types=1);

use App\Actions\Ai\ParseTransactionText;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('a failed ai provider request does not leak the raw response body', function (): void {
    Http::fake(['*' => Http::response('secret-upstream-detail', 500)]);
    Log::spy();

    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'test-key',
    ]);
    $company = Company::factory()->create();

    $exception = null;

    try {
        app(ParseTransactionText::class)->handle($user, $company, 'Bought coffee for 100');
    } catch (RuntimeException $caught) {
        $exception = $caught;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->getMessage())->not->toContain('secret-upstream-detail')
        ->and($exception->getMessage())->toBe('The AI provider request failed.');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains((string) ($context['body'] ?? ''), 'secret-upstream-detail'));
});
