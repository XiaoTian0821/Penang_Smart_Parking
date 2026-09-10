<?php
/**
 * Wallet Model
 */

namespace App\Models;

class WalletModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function getBalance(int $customerId): float {
        $stmt = $this->pdo->prepare('SELECT balance FROM wallets WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $result = $stmt->fetch();
        return $result ? (float)$result['balance'] : 0.00;
    }

    public function getOrCreate(int $customerId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            $stmt = $this->pdo->prepare('INSERT INTO wallets (customer_id, balance) VALUES (?, 0.00)');
            $stmt->execute([$customerId]);
            $wallet = ['id' => $this->pdo->lastInsertId(), 'customer_id' => $customerId, 'balance' => 0.00];
        }

        return $wallet;
    }

    public function addCredit(int $customerId, float $amount, string $transactionId, string $type = 'RELOAD'): bool {
        $pdo = $this->pdo;
        $pdo->beginTransaction();

        try {
            // Credit wallet
            $stmt = $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE customer_id = ?');
            $stmt->execute([$amount, $customerId]);

            // Record transaction
            $stmt = $pdo->prepare(
                'INSERT INTO wallet_transactions (wallet_id, customer_id, transaction_type, amount, balance_before, balance_after, reference_id, status)
                 VALUES ((SELECT id FROM wallets WHERE customer_id = ?), ?, ?, ?, (SELECT balance FROM wallets WHERE customer_id = ?), (SELECT balance FROM wallets WHERE customer_id = ?), ?, ?)'
            );
            $stmt->execute([$customerId, $customerId, $type, $amount, $customerId, $customerId, $transactionId, 'completed']);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function debit(int $customerId, float $amount, string $transactionId, string $type = 'PARKING_PAYMENT'): bool {
        $pdo = $this->pdo;
        $pdo->beginTransaction();

        try {
            // Check balance
            $stmt = $pdo->prepare('SELECT balance FROM wallets WHERE customer_id = ? FOR UPDATE');
            $stmt->execute([$customerId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float)$wallet['balance'] < $amount) {
                return false;
            }

            $balanceBefore = (float)$wallet['balance'];

            // Debit wallet
            $stmt = $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE customer_id = ?');
            $stmt->execute([$amount, $customerId]);

            // Record transaction
            $stmt = $pdo->prepare(
                'INSERT INTO wallet_transactions (wallet_id, customer_id, transaction_type, amount, balance_before, balance_after, reference_id, status)
                 VALUES ((SELECT id FROM wallets WHERE customer_id = ?), ?, ?, ?, ?, (SELECT balance FROM wallets WHERE customer_id = ?), ?, ?)'
            );
            $stmt->execute([$customerId, $customerId, $type, $amount, $balanceBefore, $customerId, $transactionId, 'completed']);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function getTransactions(int $customerId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM wallet_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }
}
