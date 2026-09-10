<?php
/**
 * Parking Session Model
 */

namespace App\Models;

class ParkingSessionModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_sessions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByVehicle(int $vehicleId): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM parking_sessions WHERE vehicle_id = ? AND status = ? ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$vehicleId, 'active']);
        return $stmt->fetch() ?: null;
    }

    public function findByPlate(string $normalizedPlate): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM parking_sessions WHERE normalized_plate = ? AND status = ? ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([strtoupper($normalizedPlate), 'active']);
        return $stmt->fetch() ?: null;
    }

    public function findByPlateAll(string $normalizedPlate): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM parking_sessions WHERE normalized_plate = ? ORDER BY created_at DESC'
        );
        $stmt->execute([strtoupper($normalizedPlate)]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $normalized = strtoupper(preg_replace('/[\s\-]+/', '', $data['plate']));
        $stmt = $this->pdo->prepare(
            'INSERT INTO parking_sessions (session_number, customer_id, vehicle_id, normalized_plate, plate_snapshot, zone_id, gps_lat, gps_lng, start_time, end_time, duration_minutes, fee, rate_snapshot, payment_transaction_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['session_number'],
            $data['customer_id'],
            $data['vehicle_id'],
            $normalized,
            $data['plate_snapshot'] ?? null,
            $data['zone_id'],
            $data['gps_lat'] ?? null,
            $data['gps_lng'] ?? null,
            $data['start_time'],
            $data['end_time'],
            $data['duration_minutes'],
            $data['fee'],
            $data['rate_snapshot'] ?? json_encode([]),
            $data['payment_transaction_id'] ?? null,
            'active'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare('UPDATE parking_sessions SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function extend(int $id, string $newEndTime, int $additionalMinutes, float $additionalFee): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE parking_sessions SET end_time = ?, duration_minutes = duration_minutes + ?, fee = fee + ? WHERE id = ? AND status = ?'
        );
        return $stmt->execute([$newEndTime, $additionalMinutes, $additionalFee, $id, 'active']);
    }

    public function getActiveByZone(int $zoneId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_sessions WHERE zone_id = ? AND status = ?');
        $stmt->execute([$zoneId, 'active']);
        return $stmt->fetchAll();
    }

    public function getCustomerSessions(int $customerId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM parking_sessions WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    public function getExpired(): array {
        $stmt = $this->pdo->query(
            "SELECT * FROM parking_sessions WHERE status = 'active' AND end_time < NOW()"
        );
        return $stmt->fetchAll();
    }

    public function count(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM parking_sessions');
        return (int)$stmt->fetchColumn();
    }
}
