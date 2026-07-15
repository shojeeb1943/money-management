<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------
    | AI providers for the transaction chatbot
    |--------------------------------------------------------------------
    |
    | Every provider except "anthropic" speaks the same OpenAI-compatible
    | chat/completions wire format, so they share the "openai" style and
    | differ only by base_url/model. "custom" lets a user point at any
    | other OpenAI-compatible endpoint (e.g. Mimo, Groq, Mistral).
    |
    */

    'providers' => [
        'google' => [
            'label' => 'Google AI Studio (free tier)',
            'style' => 'openai',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'models' => ['gemini-2.5-flash', 'gemini-2.0-flash-lite'],
        ],
        'openrouter' => [
            'label' => 'OpenRouter (many free models)',
            'style' => 'openai',
            'base_url' => 'https://openrouter.ai/api/v1',
            'models' => ['deepseek/deepseek-chat-v3.1:free', 'meta-llama/llama-3.3-70b-instruct:free'],
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'style' => 'openai',
            'base_url' => 'https://api.deepseek.com',
            'models' => ['deepseek-chat'],
        ],
        'openai' => [
            'label' => 'OpenAI',
            'style' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
        ],
        'anthropic' => [
            'label' => 'Anthropic',
            'style' => 'anthropic',
            'base_url' => 'https://api.anthropic.com/v1',
            'models' => ['claude-haiku-4-5', 'claude-sonnet-5'],
        ],
        'custom' => [
            'label' => 'Custom (OpenAI-compatible)',
            'style' => 'openai',
            'base_url' => null,
            'models' => [],
        ],
    ],
];
