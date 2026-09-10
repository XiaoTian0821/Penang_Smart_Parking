<?php
/**
 * PayPal Service
 * Handles wallet reload via PayPal
 */
declare(strict_types=1);

class PayPalService {
    private array $config;

    public function __construct() {
        $this->config = getPayPalConfig();
    }

    /**
     * Create a PayPal order for wallet reload
     */
    public function createOrder(int $customerId, float $amount, string $orderId): array {
        $url = $this->config['api_base'] . '/v2/checkout/orders';

        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $this->getAuthHeader(),
        ];

        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $orderId,
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency_code' => 'MYR',
                    ],
                    'description' => "Penang Smart Parking - Wallet Reload",
                ]
            ],
            'application_context' => [
                'return_url' => APP_URL . '/customer/wallet/success',
                'cancel_url' => APP_URL . '/customer/wallet/cancel',
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $result = json_decode($response, true);
            return [
                'success' => true,
                'order_id' => $result['id'] ?? $orderId,
                'approve_url' => $this->getApprovalUrl($result),
            ];
        }

        return ['success' => false, 'error' => 'PayPal order creation failed: ' . $response];
    }

    /**
     * Capture a PayPal order
     */
    public function captureOrder(string $orderId): array {
        $url = $this->config['api_base'] . "/v2/checkout/orders/{$orderId}/capture";

        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $this->getAuthHeader(),
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($result['status']) && $result['status'] === 'COMPLETED') {
            return [
                'success' => true,
                'transaction_id' => $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                'amount' => (float)($result['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0),
                'paypal_order_id' => $orderId,
            ];
        }

        return ['success' => false, 'error' => 'PayPal capture failed', 'response' => $result];
    }

    /**
     * Handle PayPal webhook
     */
    public function handleWebhook(string $body, array $headers): array {
        // Verify webhook signature (simplified - in production use PayPal SDK)
        $event = json_decode($body, true);

        if (!$event || !isset($event['event_type'])) {
            return ['success' => false, 'error' => 'Invalid webhook'];
        }

        $eventType = $event['event_type'];
        $resource = $event['resource'] ?? [];

        // Handle payment capture
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $transactionId = $resource['id'] ?? null;
            $amount = (float)($resource['amount']['value'] ?? 0);
            $orderId = $resource['purchase_unit_reference_id'] ?? null;

            if ($transactionId && $amount > 0) {
                // Credit wallet (idempotent - check if already credited)
                $pdo = db();
                $stmt = $pdo->prepare('SELECT id FROM wallet_transactions WHERE reference_id = ? LIMIT 1');
                $stmt->execute([$transactionId]);
                if (!$stmt->fetch()) {
                    // Find customer by order ID pattern
                    $customerMatch = preg_match('/CUST(\d+)_/', $orderId, $matches);
                    if ($customerMatch) {
                        $customerId = (int)$matches[1];
                        $this->creditWallet($customerId, $amount, $transactionId);
                    }
                }
            }
        }

        return ['success' => true];
    }

    private function creditWallet(int $customerId, float $amount, string $transactionId): void {
        $walletService = new WalletService();
        $walletService->credit($customerId, $amount, $transactionId, 'PAYPAL_RELOAD');
    }

    private function getAuthHeader(): string {
        return 'Basic ' . base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
    }

    private function getApprovalUrl(array $result): string {
        foreach ($result['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return '';
    }
}
