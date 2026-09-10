<?php
/**
 * Wallet Service
 */

class WalletService {
    private $walletModel;

    public function __construct() {
        $this->walletModel = new WalletModel();
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
        return $this->walletModel->credit($customerId, $amount, $referenceId, $type);
    }

    public function debit(int $customerId, float $amount, string $referenceId, string $type = 'PARKING_PAYMENT'): bool {
        return $this->walletModel->debit($customerId, $amount, $referenceId, $type);
    }
}
