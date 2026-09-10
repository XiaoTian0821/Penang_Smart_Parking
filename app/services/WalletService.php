<?php
/**
 * Wallet Service
 * Handles wallet operations securely
 */
declare(strict_types=1);

namespace App\Services;

class WalletService {
    private $walletModel;

    public function __construct() {
        $this->walletModel = new \App\Models\WalletModel();
    }

    public function getBalance(int $customerId): float {
        return $this->walletModel->getBalance($customerId);
    }

    public function getWallet(int $customerId): array {
        return $this->walletModel->getOrCreate($customerId);
    }

    public function getTransactions(int $customerId, int $limit = 50): array {
        return $this->walletModel->getTransactions($customerId, $limit);
    }

    public function credit(int $customerId, float $amount, string $referenceId, string $type = 'RELOAD'): bool {
        return $this->walletModel->addCredit($customerId, $amount, $referenceId, $type);
    }

    public function debit(int $customerId, float $amount, string $referenceId, string $type = 'PARKING_PAYMENT'): bool {
        return $this->walletModel->debit($customerId, $amount, $referenceId, $type);
    }
}
