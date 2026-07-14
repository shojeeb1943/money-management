<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\ClientRepository;

beforeEach(function (): void {
    Artisan::call('passport:keys');
});

test('a user can create an api token and sees it once', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('api-tokens.store'), ['name' => 'Claude Code'])
        ->assertRedirect()
        ->assertSessionHas('plainTextToken');

    expect($user->tokens()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings/api-tokens')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Claude Code'));
});

test('a user can revoke an api token', function (): void {
    $user = User::factory()->create();
    resolve(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Client', 'users');
    $token = $user->createToken('Old agent');

    $this->actingAs($user)
        ->delete(route('api-tokens.destroy', ['tokenId' => $token->getToken()->getKey()]))
        ->assertRedirect();

    expect($user->tokens()->where('revoked', false)->count())->toBe(0);
});

test('a user cannot revoke another users token', function (): void {
    $owner = User::factory()->create();
    resolve(ClientRepository::class)->createPersonalAccessGrantClient('Test Personal Client', 'users');
    $token = $owner->createToken('Mine');

    $other = User::factory()->create();

    $this->actingAs($other)
        ->delete(route('api-tokens.destroy', ['tokenId' => $token->getToken()->getKey()]))
        ->assertRedirect();

    expect($owner->tokens()->where('revoked', false)->count())->toBe(1);
});
