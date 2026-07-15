<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AiController extends Controller
{
    /**
     * Show the user's AI settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/ai', [
            'providers' => config('ai.providers'),
            'provider' => $user->ai_provider,
            'model' => $user->ai_model,
            'baseUrl' => $user->ai_base_url,
            'hasApiKey' => $this->hasDecryptableValue($user, 'ai_api_key'),
            'fallbackProvider' => $user->ai_fallback_provider,
            'fallbackModel' => $user->ai_fallback_model,
            'fallbackBaseUrl' => $user->ai_fallback_base_url,
            'hasFallbackApiKey' => $this->hasDecryptableValue($user, 'ai_fallback_api_key'),
        ]);
    }

    /**
     * Update the user's AI settings.
     */
    public function update(AiSettingsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $fallbackProvider = $request->validated('fallback_provider');

        $user->fill([
            'ai_provider' => $request->validated('provider'),
            'ai_model' => $request->validated('model'),
            'ai_base_url' => $request->validated('provider') === 'custom' ? $request->validated('base_url') : null,
            'ai_fallback_provider' => $fallbackProvider,
            'ai_fallback_model' => $fallbackProvider !== null ? $request->validated('fallback_model') : null,
            'ai_fallback_base_url' => $fallbackProvider === 'custom' ? $request->validated('fallback_base_url') : null,
        ]);

        if ($request->filled('api_key')) {
            $user->ai_api_key = $request->validated('api_key');
        }

        if ($fallbackProvider === null) {
            $user->ai_fallback_api_key = null;
        } elseif ($request->filled('fallback_api_key')) {
            $user->ai_fallback_api_key = $request->validated('fallback_api_key');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI settings updated.')]);

        return to_route('ai.edit');
    }

    /**
     * Treat an undecryptable stored value (e.g. after an APP_KEY rotation) as "not set"
     * instead of letting the DecryptException bubble into a 500.
     */
    private function hasDecryptableValue(User $user, string $attribute): bool
    {
        try {
            return filled($user->{$attribute});
        } catch (DecryptException) {
            return false;
        }
    }
}
