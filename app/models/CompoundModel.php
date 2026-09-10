<?php
/**
 * Compound Model
 */

namespace App\Models;

class CompoundModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM compounds WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByPlate(string $normalizedPlate): array {
        $stmt = $this->pdo->prepare('SELECT * FROM compounds WHERE normalized_plate = ? ORDER BY created_at DESC');
        $stmt->execute([strtoupper($normalizedPlate)]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compounds (compound_number, violation_type, normalized_plate, plate_snapshot, vehicle_id, customer_id, zone_id, detection_time, evidence_path, amount, status, issued_by, due_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['compound_number'],
            $data['violation_type'],
            strtoupper($data['normalized_plate']),
            $data['plate_snapshot'] ?? null,
            $data['vehicle_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['zone_id'],
            $data['detection_time'],
            $data['evidence_path'] ?? null,
            $data['amount'],
            $data['status'] ?? 'pending_review',
            $data['issued_by'] ?? null,
            $data['due_date']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE compounds SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $stmt = $this->pdo->prepare('SELECT * FROM compounds ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getPendingReview(): array {
        $stmt = $this->pdo->query("SELECT * FROM compounds WHERE status = 'pending_review' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getByStatus(string $status, int $limit = 50): array {
        $stmt = $this->pdo->prepare('SELECT * FROM compounds WHERE status = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM compounds');
        return (int)$stmt->fetchColumn();
    }
}
