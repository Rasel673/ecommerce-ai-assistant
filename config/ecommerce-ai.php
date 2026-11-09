<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Enabled Status
    |--------------------------------------------------------------------------
    |
    | Toggle AI assistant and analytics features across the application.
    |
    */
    'enabled' => env('ECOMMERCE_AI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The default LLM provider driver used by the chatbot and agent.
    | Supported: 'openai', 'anthropic', 'gemini', 'groq', 'deepseek', 'ollama', 'mistral'
    |
    */
    'provider' => env('ECOMMERCE_AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials, model names, and connection settings for each supported AI provider.
    | Sensitive keys should always be loaded through environment variables.
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => env('OPENAI_TIMEOUT', 30),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],


        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-20241022'),
            'timeout' => env('ANTHROPIC_TIMEOUT', 30),
            'temperature' => env('ANTHROPIC_TEMPERATURE', 0.7),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        ],


        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout' => env('GEMINI_TIMEOUT', 30),
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],

        
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'timeout' => env('GROQ_TIMEOUT', 30),
            'temperature' => env('GROQ_TEMPERATURE', 0.7),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        ],

        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'timeout' => env('DEEPSEEK_TIMEOUT', 30),
            'temperature' => env('DEEPSEEK_TEMPERATURE', 0.7),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
        ],

        'ollama' => [
            'api_key' => env('OLLAMA_API_KEY', 'ollama'),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
            'timeout' => env('OLLAMA_TIMEOUT', 60),
            'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
        ],

        'mistral' => [
            'api_key' => env('MISTRAL_API_KEY'),
            'model' => env('MISTRAL_MODEL', 'mistral-small-latest'),
            'timeout' => env('MISTRAL_TIMEOUT', 30),
            'temperature' => env('MISTRAL_TEMPERATURE', 0.7),
            'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
        ],
    ],

    // Legacy direct openai config mapping for backwards compatibility
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Assistant Behavior & Prompting
    |--------------------------------------------------------------------------
    |
    | System prompt and input size validation limits for the chatbot.
    |
    */
    'system_prompt' => env('ECOMMERCE_AI_SYSTEM_PROMPT', 'You are a helpful ecommerce AI assistant for this online store.'),
    'max_input_length' => env('ECOMMERCE_AI_MAX_INPUT_LENGTH', 2000),

    /*
    |--------------------------------------------------------------------------
    | AI Agent Configuration
    |--------------------------------------------------------------------------
    |
    | Limits and controls for the autonomous tool-calling execution loop.
    |
    */
    'agent' => [
        'max_iterations' => env('ECOMMERCE_AI_AGENT_MAX_ITERATIONS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    |
    | Define prefix and middleware groups applied to package API routes.
    |
    */
    'route' => [
        'prefix' => env('ECOMMERCE_AI_ROUTE_PREFIX', 'api/ecommerce-ai'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Currency code formatted in responses (e.g., BDT, USD, EUR).
    |
    */
    'currency' => env('ECOMMERCE_AI_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Default quantity threshold below which a product is considered low in stock.
    |
    */
    'low_stock_threshold' => env('ECOMMERCE_AI_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Ecommerce Data Adapter
    |--------------------------------------------------------------------------
    |
    | The adapter class responsible for normalizing and executing read-only
    | analytics queries against the host application database.
    |
    */
    'adapter' => env('ECOMMERCE_AI_ADAPTER', \Rasel673\EcommerceAI\Adapters\GenericEcommerceAdapter::class),

    /*
    |--------------------------------------------------------------------------
    | Table Names (For Generic Adapter)
    |--------------------------------------------------------------------------
    |
    | Configurable table names used by GenericEcommerceAdapter.
    |
    */
    'tables' => [
        'orders' => env('ECOMMERCE_AI_TABLE_ORDERS', 'orders'),
        'order_items' => env('ECOMMERCE_AI_TABLE_ORDER_ITEMS', 'order_items'),
        'products' => env('ECOMMERCE_AI_TABLE_PRODUCTS', 'products'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatbot UI Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the optional admin dashboard chatbot widget.
    |
    */
    'ui' => [
        'enabled' => env('ECOMMERCE_AI_UI_ENABLED', true),
        'title' => env('ECOMMERCE_AI_UI_TITLE', 'Store AI Assistant'),
        'accent_color' => env('ECOMMERCE_AI_UI_ACCENT_COLOR', '#7c3aed'), // Purple accent
    ],
];
