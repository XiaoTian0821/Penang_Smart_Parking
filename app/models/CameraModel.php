<?php
/**
 * Camera Model
 */

namespace App\Models;

use App\Services\CameraService;

class CameraModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function getAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM cameras ORDER BY name');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM cameras WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cameras (name, code, camera_type, zone_id, latitude, longitude, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['code'],
            $data['camera_type'] ?? 'upload',
            $data['zone_id'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['status'] ?? 'active'
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
        $stmt = $this->pdo->prepare("UPDATE cameras SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }
}
