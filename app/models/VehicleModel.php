<?php
/**
 * Vehicle Model
 */

namespace App\Models;

class VehicleModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByPlate(string $plate): ?array {
        $normalized = normalizePlate($plate);
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE normalized_plate = ? AND status = ?');
        $stmt->execute([$normalized, 'active']);
        return $stmt->fetch() ?: null;
    }

    public function findByOwner(int $ownerId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE owner_id = ? ORDER BY created_at DESC');
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $normalized = normalizePlate($data['plate']);
        $stmt = $this->pdo->prepare('
            INSERT INTO vehicles (owner_id, plate, normalized_plate, vehicle_type, color, make, model, year, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['owner_id'],
            $data['plate'],
            $normalized,
            $data['vehicle_type'] ?? 'car',
            $data['color'] ?? null,
            $data['make'] ?? null,
            $data['model'] ?? null,
            $data['year'] ?? null,
            'active',
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
        $stmt = $this->pdo->prepare('UPDATE vehicles SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('UPDATE vehicles SET status = ? WHERE id = ?');
        return $stmt->execute(['inactive', $id]);
    }

    public function search(string $query): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM vehicles 
            WHERE plate LIKE ? OR normalized_plate LIKE ? 
            ORDER BY created_at DESC LIMIT 50
        ");
        $stmt->execute(["%$query%", "%$query%"]);
        return $stmt->fetchAll();
    }
}
