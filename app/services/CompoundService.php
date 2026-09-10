<?php
/**
 * Compound Service
 */

namespace App\Services;

use App\Models\CompoundModel;
use App\Models\WalletModel;
use App\Models\NotificationModel;

class CompoundService {
    private $compoundModel;
    private $walletModel;
    private $notificationModel;
    private $pdo;

    public function __construct() {
        $this->compoundModel = new CompoundModel();
        $this->walletModel = new WalletModel();
        $this->notificationModel = new NotificationModel();
        $this->pdo = db();
    }

    public function createCompound(array $data): int {
        $compoundNumber = 'CMP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        $data['compound_number'] = $compoundNumber;
        $data['status'] = $data['status'] ?? 'pending_review';
        $data['issued_by'] = $data['issued_by'] ?? (currentUser()['id'] ?? null);
        $data['due_date'] = date('Y-m-d H:i:s', strtotime('+30 days'));

        return $this->compoundModel->create($data);
    }

    public function reviewCompound(int $compoundId, int $officerId, string $decision, string $remarks = ''): array {
        $compound = $this->compoundModel->findById($compoundId);
        if (!$compound) {
            return ['success' => false, 'error' => 'Compound not found'];
        }

        if ($compound['status'] !== 'pending_review') {
            return ['success' => false, 'error' => 'Compound is not pending review'];
        }

        $this->pdo->beginTransaction();

        try {
            if ($decision === 'issued') {
                $this->compoundModel->update($compoundId, [
                    'status' => 'issued',
                    'reviewed_by' => $officerId,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'review_remarks' => $remarks,
                ]);

                if ($compound['customer_id']) {
                    $this->notificationModel->create(
                        $compound['customer_id'],
                        'compound_issued',
                        'Parking Violation Issued',
                        "A parking violation ({$compound['violation_type']}) has been issued for plate {$compound['normalized_plate']}. Amount: RM {$compound['amount']}."
                    );
                }
            } elseif ($decision === 'rejected') {
                $this->compoundModel->update($compoundId, [
                    'status' => 'cancelled',
                    'reviewed_by' => $officerId,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'review_remarks' => $remarks,
                ]);
            }

            $this->pdo->commit();
            logEvent('enforcement', "Compound {$compoundId} reviewed: {$decision} by officer {$officerId}");

            return ['success' => true, 'decision' => $decision];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => 'Failed to process compound review'];
        }
    }

    public function payCompound(int $compoundId, int $customerId): bool {
        $compound = $this->compoundModel->findById($compoundId);
        if (!$compound || $compound['status'] !== 'issued') {
            return false;
        }

        if ($compound['customer_id'] !== $customerId) {
            return false;
        }

        $success = $this->walletModel->debit(
            $customerId,
            (float)$compound['amount'],
            "CPD-{$compoundId}",
            'COMPOUND_PAYMENT'
        );

        if ($success) {
            $this->compoundModel->update($compoundId, [
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
            ]);

            $this->notificationModel->create(
                $customerId,
                'compound_paid',
                'Compound Paid',
                "Your compound {$compound['compound_number']} has been paid. Amount: RM {$compound['amount']}."
            );

            logEvent('payment', "Compound {$compoundId} paid by customer {$customerId}: RM {$compound['amount']}");
        }

        return $success;
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        return $this->compoundModel->getAll($limit, $offset);
    }

    public function getPendingReview(): array {
        return $this->compoundModel->getPendingReview();
    }
}
