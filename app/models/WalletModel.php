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

    public function getOrCreate(int $customerId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            $stmt = $this->pdo->prepare('INSERT INTO wallets (customer_id, balance) VALUES (?, ?)');
            $stmt->execute([$customerId, 0.00]);
            $wallet = ['id' => $this->pdo->lastInsertId(), 'customer_id' => $customerId, 'balance' => 0.00];
        }
        
        return $wallet;
    }

    public function getBalance(int $customerId): float {
        $stmt = $this->pdo->prepare('SELECT balance FROM wallets WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        return $row ? (float)$row['balance'] : 0.00;
    }

    public function debit(int $customerId, float $amount, string $referenceId, string $type = 'PARKING_PAYMENT'): bool {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }
        
        try {
            // Get current balance
            $stmt = $this->pdo->prepare('SELECT id, balance FROM wallets WHERE customer_id = ? FOR UPDATE');
            $stmt->execute([$customerId]);
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                if ($ownsTransaction) {
                    $this->pdo->rollBack();
                }
                return false;
            }
            
            $balanceBefore = (float)$wallet['balance'];
            $balanceAfter = $balanceBefore - $amount;
            
            if ($balanceAfter < 0) {
                if ($ownsTransaction) {
                    $this->pdo->rollBack();
                }
                return false;
            }
            
            // Update wallet balance
            $stmt = $this->pdo->prepare('UPDATE wallets SET balance = ? WHERE customer_id = ?');
            $stmt->execute([$balanceAfter, $customerId]);
            
            // Record transaction
            $stmt = $this->pdo->prepare('
                INSERT INTO wallet_transactions 
                (wallet_id, customer_id, transaction_type, amount, balance_before, balance_after, reference_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $wallet['id'],
                $customerId,
                $type,
                -$amount,
                $balanceBefore,
                $balanceAfter,
                $referenceId,
                'completed',
            ]);
            
            if ($ownsTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            logEvent('wallet_error', "Wallet debit failed: " . $e->getMessage(), [
                'customer_id' => $customerId,
                'amount' => $amount,
                'reference_id' => $referenceId,
            ]);
            return false;
        }
    }

    public function credit(int $customerId, float $amount, string $referenceId, string $type = 'RELOAD'): bool {
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare('SELECT balance FROM wallets WHERE customer_id = ? FOR UPDATE');
            $stmt->execute([$customerId]);
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                // Create wallet if doesn't exist
                $stmt = $this->pdo->prepare('INSERT INTO wallets (customer_id, balance) VALUES (?, ?)');
                $stmt->execute([$customerId, $amount]);
                $walletId = $this->pdo->lastInsertId();
                $balanceBefore = 0.00;
                $balanceAfter = $amount;
            } else {
                $balanceBefore = (float)$wallet['balance'];
                $balanceAfter = $balanceBefore + $amount;
                $walletId = $wallet['id'];
                
                $stmt = $this->pdo->prepare('UPDATE wallets SET balance = ? WHERE customer_id = ?');
                $stmt->execute([$balanceAfter, $customerId]);
            }
            
            $stmt = $this->pdo->prepare('
                INSERT INTO wallet_transactions 
                (wallet_id, customer_id, transaction_type, amount, balance_before, balance_after, reference_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $walletId,
                $customerId,
                $type,
                $amount,
                $balanceBefore,
                $balanceAfter,
                $referenceId,
                'completed',
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getTransactions(int $customerId, int $limit = 50): array {
        $stmt = $this->pdo->prepare('
            SELECT * FROM wallet_transactions 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }
}
