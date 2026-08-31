<?php

// Ingestion triggers. Public routes, but every one verifies the sender
// (signature/token/clientState) before doing anything with the payload —
// see the individual controllers under App\Http\Controllers\Webhooks.

use App\Http\Controllers\Webhooks\GmailPushWebhookController;
use App\Http\Controllers\Webhooks\InboundEmailWebhookController;
use App\Http\Controllers\Webhooks\OutlookNotificationWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('inbound-email', [InboundEmailWebhookController::class, 'handle']);
Route::post('gmail-push', [GmailPushWebhookController::class, 'handle']);
Route::match(['get', 'post'], 'outlook-notifications', [OutlookNotificationWebhookController::class, 'handle']);
