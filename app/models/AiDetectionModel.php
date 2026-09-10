<?php
/**
 * AI Detection Model
 */

namespace App\Models;

class AiDetectionModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO ai_detections (plate, confidence, model_used, processing_time, image_path, status, enforcement_result)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['plate'] ?? null,
            $data['confidence'] ?? null,
            $data['model_used'] ?? null,
            $data['processing_time'] ?? null,
            $data['image_path'] ?? null,
            $data['status'] ?? 'processed',
            $data['enforcement_result'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getRecent(int $limit = 20): array {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_detections ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
