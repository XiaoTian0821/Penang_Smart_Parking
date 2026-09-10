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
        $stmt = $this->pdo->prepare("
            SELECT * FROM parking_sessions 
            WHERE vehicle_id = ? AND status = 'active' 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$vehicleId]);
        return $stmt->fetch() ?: null;
    }

    public function findByPlate(string $plate): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM parking_sessions 
            WHERE normalized_plate = ? AND status = 'active' 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$plate]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO parking_sessions 
            (session_number, customer_id, vehicle_id, normalized_plate, zone_id, gps_lat, gps_lng, 
             start_time, end_time, duration_minutes, fee, rate_snapshot, payment_transaction_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['session_number'],
            $data['customer_id'],
            $data['vehicle_id'],
            $data['normalized_plate'],
            $data['zone_id'],
            $data['gps_lat'] ?? null,
            $data['gps_lng'] ?? null,
            $data['start_time'],
            $data['end_time'],
            $data['duration_minutes'],
            $data['fee'],
            $data['rate_snapshot'] ?? null,
            $data['payment_transaction_id'] ?? null,
            $data['status'] ?? 'active',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $sessionId, string $status): bool {
        $stmt = $this->pdo->prepare('UPDATE parking_sessions SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $sessionId]);
    }

    public function count(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM parking_sessions');
        return (int)$stmt->fetchColumn();
    }

    public function extend(int $sessionId, string $newEndTime, int $additionalMinutes, float $fee): bool {
        $stmt = $this->pdo->prepare('
            UPDATE parking_sessions 
            SET end_time = ?, duration_minutes = duration_minutes + ?, fee = fee + ? 
            WHERE id = ?
        ');
        return $stmt->execute([$newEndTime, $additionalMinutes, $fee, $sessionId]);
    }

    public function getCustomerSessions(int $customerId, int $limit = 50): array {
        $stmt = $this->pdo->prepare('
            SELECT * FROM parking_sessions 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    public function getActiveByZone(int $zoneId): array {
        $stmt = $this->pdo->prepare('
            SELECT * FROM parking_sessions 
            WHERE zone_id = ? AND status = ?
            ORDER BY start_time DESC
        ');
        $stmt->execute([$zoneId, 'active']);
        return $stmt->fetchAll();
    }
}
