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

    public function findByPlate(string $normalizedPlate): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE normalized_plate = ?');
        $stmt->execute([strtoupper($normalizedPlate)]);
        return $stmt->fetch() ?: null;
    }

    public function findByOwner(int $ownerId): array {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE owner_id = ? ORDER BY created_at DESC');
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $normalized = strtoupper(preg_replace('/[\s\-]+/', '', $data['plate']));
        $stmt = $this->pdo->prepare(
            'INSERT INTO vehicles (owner_id, plate, normalized_plate, vehicle_type, color, make, model, year, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['owner_id'],
            $data['plate'],
            $normalized,
            $data['vehicle_type'] ?? 'car',
            $data['color'] ?? null,
            $data['make'] ?? null,
            $data['model'] ?? null,
            $data['year'] ?? null,
            'active'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, int $ownerId, array $data): bool {
        // Verify ownership
        $stmt = $this->pdo->prepare('SELECT id FROM vehicles WHERE id = ? AND owner_id = ?');
        $stmt->execute([$id, $ownerId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE vehicles SET plate = ?, vehicle_type = ?, color = ?, make = ?, model = ?, year = ? WHERE id = ?'
        );
        return $stmt->execute([
            $data['plate'],
            $data['vehicle_type'] ?? 'car',
            $data['color'] ?? null,
            $data['make'] ?? null,
            $data['model'] ?? null,
            $data['year'] ?? null,
            $id
        ]);
    }

    public function delete(int $id, int $ownerId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM vehicles WHERE id = ? AND owner_id = ?');
        return $stmt->execute([$id, $ownerId]);
    }

    public function search(string $query, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vehicles WHERE normalized_plate LIKE ? OR plate LIKE ? LIMIT ?'
        );
        $like = '%' . strtoupper($query) . '%';
        $stmt->execute([$like, $like, $limit]);
        return $stmt->fetchAll();
    }
}
