<?php

declare(strict_types=1);

use App\Models\User;

test('ai settings page is displayed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('ai.edit'))->assertOk();
});

test('ai settings can be updated', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('ai.update'), [
        'provider' => 'google',
        'model' => 'gemini-2.5-flash',
        'api_key' => 'secret-key',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('ai.edit'));

    $user->refresh();

    expect($user->ai_provider)->toBe('google')
        ->and($user->ai_model)->toBe('gemini-2.5-flash')
        ->and($user->ai_api_key)->toBe('secret-key');
});

test('a blank api key does not overwrite the stored key', function (): void {
    $user = User::factory()->create([
        'ai_provider' => 'google',
        'ai_model' => 'gemini-2.5-flash',
        'ai_api_key' => 'secret-key',
    ]);

    $this->actingAs($user)->patch(route('ai.update'), [
        'provider' => 'google',
        'model' => 'gemini-2.0-flash-lite',
        'api_key' => '',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->ai_api_key)->toBe('secret-key')
        ->and($user->ai_model)->toBe('gemini-2.0-flash-lite');
});

test('the custom provider requires a base url', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('ai.update'), [
        'provider' => 'custom',
        'model' => 'my-model',
    ])->assertSessionHasErrors('base_url');
});

test('a fallback provider can be configured alongside the primary', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('ai.update'), [
        'provider' => 'google',
        'model' => 'gemini-2.5-flash',
        'api_key' => 'primary-key',
        'fallback_provider' => 'anthropic',
        'fallback_model' => 'claude-haiku-4-5',
        'fallback_api_key' => 'fallback-key',
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->ai_fallback_provider)->toBe('anthropic')
        ->and($user->ai_fallback_model)->toBe('claude-haiku-4-5')
        ->and($user->ai_fallback_api_key)->toBe('fallback-key');
});

test('clearing the fallback provider also clears its stored api key', function (): void {
    $user = User::factory()->create([
        'ai_fallback_provider' => 'anthropic',
        'ai_fallback_model' => 'claude-haiku-4-5',
        'ai_fallback_api_key' => 'fallback-key',
    ]);

    $this->actingAs($user)->patch(route('ai.update'), [
        'provider' => 'google',
        'model' => 'gemini-2.5-flash',
        'fallback_provider' => '',
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->ai_fallback_provider)->toBeNull()
        ->and($user->ai_fallback_api_key)->toBeNull();
});
