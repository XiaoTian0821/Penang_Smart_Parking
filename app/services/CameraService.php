<?php
/**
 * Camera Service
 * Handles image capture, validation, and storage
 */
declare(strict_types=1);

class CameraService {
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Save uploaded image and return path
     */
    public function saveUpload(array $fileData): array {
        // Validate file
        $validation = $this->validateFile($fileData);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Generate random filename
        $ext = $this->getExtension($validation['mime']);
        $filename = bin2hex(random_bytes(16)) . $ext;
        $path = EVIDENCE_PATH . '/' . $filename;

        // Move file
        if (move_uploaded_file($fileData['tmp_name'], $path)) {
            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
                'mime' => $validation['mime'],
            ];
        }

        return ['success' => false, 'error' => 'Failed to save image file'];
    }

    /**
     * Save base64 encoded image
     */
    public function saveBase64(string $base64Data, string $mimeType = 'image/jpeg'): array {
        // Decode
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return ['success' => false, 'error' => 'Invalid base64 data'];
        }

        // Validate MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES)) {
            return ['success' => false, 'error' => 'Invalid file type: ' . $detectedMime];
        }

        // Validate size
        if (strlen($imageData) > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'Image too large (max 10MB)'];
        }

        // Generate filename
        $ext = $this->getExtension($detectedMime);
        $filename = bin2hex(random_bytes(16)) . $ext;
        $path = EVIDENCE_PATH . '/' . $filename;

        if (file_put_contents($path, $imageData) !== false) {
            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
                'mime' => $detectedMime,
            ];
        }

        return ['success' => false, 'error' => 'Failed to save image'];
    }

    private function validateFile(array $fileData): array {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error: ' . $fileData['error']];
        }

        // Validate size
        if ($fileData['size'] > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File too large (max 10MB)'];
        }

        // Validate MIME type using finfo (not $_FILES['type'])
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME_TYPES)) {
            return ['valid' => false, 'error' => 'Invalid file type: ' . $mime];
        }

        // Validate it's actually an image
        $imageInfo = getimagesize($fileData['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File is not a valid image'];
        }

        return ['valid' => true, 'mime' => $mime];
    }

    private function getExtension(string $mime): string {
        return match ($mime) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/webp' => '.webp',
            'image/gif' => '.gif',
            default => '.jpg',
        };
    }

    /**
     * Verify evidence file access
     */
    public function verifyEvidenceAccess(string $filename): bool {
        // Ensure filename doesn't contain path traversal
        if (strpos($filename, '/') !== false || strpos($filename, '..') !== false) {
            return false;
        }

        $path = EVIDENCE_PATH . '/' . $filename;
        return file_exists($path) && is_readable($path);
    }
}
