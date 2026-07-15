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
