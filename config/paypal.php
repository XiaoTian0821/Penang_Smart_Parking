<?php
/**
 * PayPal Configuration
 */
declare(strict_types=1);

function getPayPalConfig(): array {
    return [
        'mode' => getenv('PAYPAL_MODE') ?: 'sandbox',
        'client_id' => getenv('PAYPAL_CLIENT_ID') ?: '',
        'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: '',
        'webhook_id' => getenv('PAYPAL_WEBHOOK_ID') ?: '',
        'api_base' => getenv('PAYPAL_MODE') === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com',
    ];
}
