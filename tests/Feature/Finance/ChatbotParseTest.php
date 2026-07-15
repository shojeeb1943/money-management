<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('chatbot parse resolves the wallet and category from the ai response', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'test-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = $company->wallets()->where('name', 'Cash')->firstOrFail();
    $category = $company->categories()->where('kind', 'expense')->whereNull('parent_id')->firstOrFail();

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'type' => 'expense',
                    'amount' => 500,
                    'date' => null,
                    'description' => 'Lunch',
                    'wallet_name' => $wallet->name,
                    'counter_wallet_name' => null,
                    'category_name' => $category->name,
                ])]],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'spent 500 on lunch today',
        ])
        ->assertOk()
        ->assertJson([
            'type' => 'expense',
            'amount' => 500.0,
            'description' => 'Lunch',
            'walletId' => $wallet->id,
            'categoryId' => $category->id,
        ]);
});

test('chatbot parse requires an ai provider to be configured', function (): void {
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'spent 500 on lunch today',
        ])
        ->assertStatus(422);
});

test('chatbot parse falls back to the secondary provider when the primary fails', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'primary-key',
        'ai_fallback_provider' => 'anthropic',
        'ai_fallback_model' => 'claude-haiku-4-5',
        'ai_fallback_api_key' => 'fallback-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = $company->wallets()->where('name', 'Cash')->firstOrFail();

    Http::fake([
        'api.openai.com/*' => Http::response('insufficient balance', 402),
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => json_encode([
                    'type' => 'expense',
                    'amount' => 250,
                    'date' => null,
                    'description' => 'Coffee',
                    'wallet_name' => $wallet->name,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'coffee 250',
        ])
        ->assertOk()
        ->assertJson([
            'type' => 'expense',
            'amount' => 250.0,
            'walletId' => $wallet->id,
        ]);

    Http::assertSentCount(2);
});

test('chatbot parse fails when both the primary and fallback provider fail', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'primary-key',
        'ai_fallback_provider' => 'anthropic',
        'ai_fallback_model' => 'claude-haiku-4-5',
        'ai_fallback_api_key' => 'fallback-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    Http::fake([
        'api.openai.com/*' => Http::response('insufficient balance', 402),
        'api.anthropic.com/*' => Http::response('rate limited', 429),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'coffee 250',
        ])
        ->assertStatus(422);
});

test('chatbot parse falls back when the primary provider connection fails, not just on an error response', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'primary-key',
        'ai_fallback_provider' => 'anthropic',
        'ai_fallback_model' => 'claude-haiku-4-5',
        'ai_fallback_api_key' => 'fallback-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = $company->wallets()->where('name', 'Cash')->firstOrFail();

    Http::fake([
        'api.openai.com/*' => fn () => throw new Illuminate\Http\Client\ConnectionException('Connection timed out'),
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => json_encode([
                    'type' => 'expense',
                    'amount' => 100,
                    'date' => null,
                    'description' => null,
                    'wallet_name' => $wallet->name,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'taxi 100',
        ])
        ->assertOk()
        ->assertJson(['amount' => 100.0, 'walletId' => $wallet->id]);
});

test('chatbot parse falls back when the primary provider itself is misconfigured', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => null,
        'ai_fallback_provider' => 'anthropic',
        'ai_fallback_model' => 'claude-haiku-4-5',
        'ai_fallback_api_key' => 'fallback-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = $company->wallets()->where('name', 'Cash')->firstOrFail();

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'text', 'text' => json_encode([
                    'type' => 'expense',
                    'amount' => 75,
                    'date' => null,
                    'description' => null,
                    'wallet_name' => $wallet->name,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'snacks 75',
        ])
        ->assertOk()
        ->assertJson(['amount' => 75.0]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
});

test('chatbot parse handles an anthropic response that returns a thinking block before the text block', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'anthropic',
        'ai_model' => 'claude-sonnet-5',
        'ai_api_key' => 'test-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = $company->wallets()->where('name', 'Cash')->firstOrFail();

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'thinking', 'thinking' => 'Let me work this out...'],
                ['type' => 'text', 'text' => json_encode([
                    'type' => 'expense',
                    'amount' => 300,
                    'date' => null,
                    'description' => null,
                    'wallet_name' => $wallet->name,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'groceries 300',
        ])
        ->assertOk()
        ->assertJson(['amount' => 300.0, 'walletId' => $wallet->id]);
});

test('chatbot parse normalizes a comma/currency-formatted amount string', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'test-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'type' => 'expense',
                    'amount' => '৳1,500',
                    'date' => null,
                    'description' => null,
                    'wallet_name' => null,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])]],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'rent 1500',
        ])
        ->assertOk()
        ->assertJson(['amount' => 1500.0]);
});

test('chatbot parse treats a miscased type from the ai as the matching type, not a silent expense default', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'ai_api_key' => 'test-key',
    ]);
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'type' => 'Income',
                    'amount' => 1000,
                    'date' => null,
                    'description' => null,
                    'wallet_name' => null,
                    'counter_wallet_name' => null,
                    'category_name' => null,
                ])]],
            ],
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('chatbot.parse', ['current_company' => $company->slug]), [
            'text' => 'received 1000',
        ])
        ->assertOk()
        ->assertJson(['type' => 'income']);
});
