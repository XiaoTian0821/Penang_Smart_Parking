<?php
/**
 * User Model
 */

namespace App\Models;

class UserModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password, full_name, phone, ic_number, role, status, permissions)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['phone'] ?? null,
            $data['ic_number'] ?? null,
            $data['role'] ?? 'customer',
            $data['status'] ?? 'active',
            $data['permissions'] ?? '[]'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $fields[] = "password = ?";
                $values[] = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
            } else {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        $values[] = $id;
        $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function getAll(int $limit = 100, int $offset = 0): array {
        $stmt = $this->pdo->prepare('SELECT id, email, full_name, phone, ic_number, role, status, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        return (int)$stmt->fetchColumn();
    }
}
