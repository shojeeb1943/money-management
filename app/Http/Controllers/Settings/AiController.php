<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AiSettingsUpdateRequest;
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
            'hasApiKey' => filled($user->ai_api_key),
        ]);
    }

    /**
     * Update the user's AI settings.
     */
    public function update(AiSettingsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill([
            'ai_provider' => $request->validated('provider'),
            'ai_model' => $request->validated('model'),
            'ai_base_url' => $request->validated('provider') === 'custom' ? $request->validated('base_url') : null,
        ]);

        if ($request->filled('api_key')) {
            $user->ai_api_key = $request->validated('api_key');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI settings updated.')]);

        return to_route('ai.edit');
    }
}
