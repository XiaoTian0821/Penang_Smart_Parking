<?php
/**
 * Zone Model
 */

namespace App\Models;

class ZoneModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_zones WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCode(string $code): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_zones WHERE code = ?');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM parking_zones ORDER BY name');
        return $stmt->fetchAll();
    }

    public function getActive(): array {
        $stmt = $this->pdo->prepare('SELECT * FROM parking_zones WHERE status = ? ORDER BY name');
        $stmt->execute(['active']);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO parking_zones (name, code, address, latitude, longitude, hourly_rate, max_duration, capacity, available_spaces, operating_hours, enforce_outside_hours, status, qr_token)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['address'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['hourly_rate'] ?? 1.00,
            $data['max_duration'] ?? 120,
            $data['capacity'] ?? 100,
            $data['available_spaces'] ?? $data['capacity'] ?? 100,
            $data['operating_hours'] ?? json_encode([]),
            $data['enforce_outside_hours'] ?? 0,
            $data['status'] ?? 'active',
            $data['qr_token'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare('UPDATE parking_zones SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function updateAvailableSpaces(int $zoneId, int $available): bool {
        $stmt = $this->pdo->prepare('UPDATE parking_zones SET available_spaces = ? WHERE id = ?');
        return $stmt->execute([$available, $zoneId]);
    }
}
