<?php
/**
 * Appeal Model
 */

namespace App\Models;

class AppealModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM appeals WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByCompound(int $compoundId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM appeals WHERE compound_id = ?');
        $stmt->execute([$compoundId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO appeals (compound_id, customer_id, reason)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$data['compound_id'], $data['customer_id'], $data['reason']]);
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
        $stmt = $this->pdo->prepare('UPDATE appeals SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    public function getAll(int $limit = 100): array {
        $stmt = $this->pdo->prepare('SELECT * FROM appeals ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
