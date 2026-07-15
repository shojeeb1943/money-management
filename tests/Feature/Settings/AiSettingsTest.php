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
