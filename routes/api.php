<?php

use Illuminate\Support\Facades\Route;
use Rasel673\EcommerceAI\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Ecommerce AI Package API Routes
|--------------------------------------------------------------------------
|
| Routes registered by the package for AI chat and analytics operations.
| Route prefix and middleware are fully configurable via config/ecommerce-ai.php.
|
*/

Route::prefix(config('ecommerce-ai.route.prefix', 'api/ecommerce-ai'))
    ->middleware(config('ecommerce-ai.route.middleware', ['api']))
    ->group(function () {
        Route::post('/chat', [ChatController::class, 'chat'])->name('ecommerce-ai.chat');
    });

