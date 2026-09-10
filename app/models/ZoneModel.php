<?php
/**
 * Parking Zone Model
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
        $stmt->execute([strtoupper($code)]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM parking_zones ORDER BY name');
        return $stmt->fetchAll();
    }

    public function getActive(): array {
        $stmt = $this->pdo->query("SELECT * FROM parking_zones WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO parking_zones (name, code, address, latitude, longitude, hourly_rate, max_duration, capacity, status, operating_hours, weekend_rules, holiday_rules, enforce_outside_hours)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            strtoupper($data['code']),
            $data['address'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['hourly_rate'] ?? 1.00,
            $data['max_duration'] ?? 240,
            $data['capacity'] ?? 100,
            $data['status'] ?? 'active',
            $data['operating_hours'] ?? json_encode(['monday' => ['start' => '07:00', 'end' => '22:00'], 'tuesday' => ['start' => '07:00', 'end' => '22:00'], 'wednesday' => ['start' => '07:00', 'end' => '22:00'], 'thursday' => ['start' => '07:00', 'end' => '22:00'], 'friday' => ['start' => '07:00', 'end' => '22:00'], 'saturday' => ['start' => '08:00', 'end' => '20:00'], 'sunday' => ['start' => '08:00', 'end' => '20:00']]),
            $data['weekend_rules'] ?? json_encode([]),
            $data['holiday_rules'] ?? json_encode([]),
            $data['enforce_outside_hours'] ?? 0
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['id', 'created_at'])) {
                $fields[] = "$key = ?";
                $values[] = is_array($value) ? json_encode($value) : $value;
            }
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE parking_zones SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function updateAvailableSpaces(int $id, int $available): bool {
        $stmt = $this->pdo->prepare('UPDATE parking_zones SET available_spaces = ? WHERE id = ?');
        return $stmt->execute([$available, $id]);
    }
}
