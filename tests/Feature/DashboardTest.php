<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Actions\Obligations\CreateObligation;
use App\Enums\ObligationKind;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $user = User::factory()->create();
    $company = $user->currentCompany;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $company = $user->currentCompany;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard summarizes obligations by kind, excluding archived ones', function (): void {
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $wallet = Wallet::query()->firstOrFail();

    resolve(CreateObligation::class)->handle($company, ObligationKind::Loan, 'Borrowed from Nadia', $wallet, 50_000, creator: $user);
    resolve(CreateObligation::class)->handle($company, ObligationKind::Lend, 'Lent to Rafi', $wallet, 30_000, creator: $user);

    $archivedLend = resolve(CreateObligation::class)->handle($company, ObligationKind::Lend, 'Lent to Karim', $wallet, 20_000, creator: $user);
    $archivedLend->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard', ['current_company' => $company->slug]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('obligationSummary', [
                ['kind' => 'loan', 'label' => 'Loan', 'remaining' => 50_000, 'count' => 1],
                ['kind' => 'lend', 'label' => 'Lend', 'remaining' => 30_000, 'count' => 1],
                ['kind' => 'safekeeping', 'label' => 'Safekeeping', 'remaining' => 0, 'count' => 0],
            ]));
});
