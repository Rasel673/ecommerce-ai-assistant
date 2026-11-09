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
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials, default model, and request timeout settings.
    | Sensitive keys should always be loaded through environment variables.
    |
    */
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
        'accent_color' => '#7c3aed', // Purple accent
    ],
];

