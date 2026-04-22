<?php

use DevWizard\Payify\Http\Controllers\CallbackController;
use DevWizard\Payify\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhook/{provider}', WebhookController::class)->name('payify.webhook');

Route::match(['get', 'post'], 'callback/{provider}/{result?}', CallbackController::class)
    ->name('payify.callback');
