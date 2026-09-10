<?php
/**
 * Compound Service
 * Handles violation compounds
 */
declare(strict_types=1);

namespace App\Services;

class CompoundService {
    private $compoundModel;
    private $walletModel;
    private $notificationModel;
    private $pdo;

    public function __construct() {
        $this->compoundModel = new \App\Models\CompoundModel();
        $this->walletModel = new \App\Models\WalletModel();
        $this->notificationModel = new \App\Models\NotificationModel();
        $this->pdo = db();
    }

    /**
     * Create a compound from enforcement result
     */
    public function createCompound(array $data): int {
        // Generate compound number
        $compoundNumber = 'CMP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        $data['compound_number'] = $compoundNumber;
        $data['status'] = $data['status'] ?? 'pending_review';
        $data['issued_by'] = $data['issued_by'] ?? (currentUser()['id'] ?? null);
        $data['due_date'] = date('Y-m-d H:i:s', strtotime('+30 days'));

        return $this->compoundModel->create($data);
    }

    /**
     * Review and issue a pending compound
     */
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

                // Notify customer
                if ($compound['customer_id']) {
                    $this->notificationModel->create(
                        $compound['customer_id'],
                        'compound_issued',
                        'Parking Violation Issued',
                        "A parking violation ({$compound['violation_type']}) has been issued for plate {$compound['normalized_plate']}. Amount: RM {$compound['amount']}. Due date: " . date('M d, Y', strtotime($compound['due_date'])) . "."
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

    /**
     * Process compound payment
     */
    public function payCompound(int $compoundId, int $customerId): bool {
        $compound = $this->compoundModel->findById($compoundId);
        if (!$compound || $compound['status'] !== 'issued') {
            return false;
        }

        if ($compound['customer_id'] !== $customerId) {
            return false;
        }

        // Debit wallet
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
