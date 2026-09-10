<?php
/**
 * Gemini AI Service
 * Handles vehicle plate recognition via Google Gemini Vision API
 */
declare(strict_types=1);

namespace App\Services;

require_once __DIR__ . '/../../config/gemini.php';

class GeminiService {
    private array $config;
    private int $requestCount = 0;

    public function __construct() {
        $this->config = getGeminiConfig();
    }

    /**
     * Recognize vehicle plate from image
     */
    public function recognizePlate(string $imagePath, string $mimeType = 'image/jpeg'): array {
        $apiKey = $this->config['api_key'];

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'Gemini API key not configured',
                'plate' => null,
                'confidence' => 0.0,
            ];
        }

        // Read image
        $imageData = base64_encode(file_get_contents($imagePath));
        if ($imageData === false) {
            return [
                'success' => false,
                'error' => 'Failed to read image file',
                'plate' => null,
                'confidence' => 0.0,
            ];
        }

        $prompt = <<<PROMPT
            Analyze this vehicle image. Detect the vehicle registration plate.
            Return a JSON response with these fields ONLY:
            - vehicle_detected: boolean
            - plate_detected: boolean
            - plate_number: string or null
            - confidence: number between 0.0 and 1.0
            - reason: string explaining the result

            Rules:
            - If the plate is not clearly visible, set plate_detected to false and plate_number to null
            - Do NOT guess or invent a plate number
            - Confidence must be honest based on image quality and plate clarity
            - Only return valid JSON, no additional text
        PROMPT;

        // Try primary model first
        $result = $this->callGemini($imageData, $mimeType, $prompt, $this->config['primary_model']);

        // Fallback if primary fails
        if (!$result['success'] && $this->config['fallback_model'] !== $this->config['primary_model']) {
            logEvent('ai', "Primary model {$this->config['primary_model']} failed, trying fallback {$this->config['fallback_model']}");
            $result = $this->callGemini($imageData, $mimeType, $prompt, $this->config['fallback_model']);
        }

        if (!$result['success']) {
            logEvent('ai', "All Gemini models failed for image: " . $imagePath);
        }

        return $result;
    }

    private function callGemini(string $imageData, string $mimeType, string $prompt, string $model): array {
        $startTime = microtime(true);

        $url = str_replace(['{model}', '{key}'], [$model, $this->config['api_key']], $this->config['api_url']);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 500,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $processingTime = microtime(true) - $startTime;

        if ($error) {
            logEvent('ai_error', "cURL error: $error | Model: $model | Time: {$processingTime}s");
            return [
                'success' => false,
                'error' => "cURL error: $error",
                'plate' => null,
                'confidence' => 0.0,
                'model' => $model,
                'processing_time' => $processingTime,
            ];
        }

        if ($httpCode !== 200) {
            logEvent('ai_error', "HTTP {$httpCode} | Model: $model | Response: " . substr($response, 0, 200));
            return [
                'success' => false,
                'error' => "HTTP error: {$httpCode}",
                'plate' => null,
                'confidence' => 0.0,
                'model' => $model,
                'processing_time' => $processingTime,
            ];
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            logEvent('ai_error', "Invalid response structure | Model: $model");
            return [
                'success' => false,
                'error' => 'Invalid API response structure',
                'plate' => null,
                'confidence' => 0.0,
                'model' => $model,
                'processing_time' => $processingTime,
            ];
        }

        $rawText = $data['candidates'][0]['content']['parts'][0]['text'];

        // Parse JSON from response
        $parsed = $this->parseGeminiResponse($rawText);

        logAiDetection(
            $parsed['plate'] ?? 'null',
            $parsed['confidence'] ?? 0.0,
            $model,
            $processingTime,
            $parsed['success'] ?? false
        );

        $parsed['model'] = $model;
        $parsed['processing_time'] = $processingTime;

        return $parsed;
    }

    private function parseGeminiResponse(string $text): array {
        // Try to extract JSON from the response
        $jsonMatch = null;
        if (preg_match('/\{[^}]+\}/', $text, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[0], true);
            if ($decoded && isset($decoded['vehicle_detected'])) {
                return $this->validateAiResponse($decoded);
            }
        }

        // Fallback: try direct JSON decode
        $decoded = json_decode($text, true);
        if ($decoded && isset($decoded['vehicle_detected'])) {
            return $this->validateAiResponse($decoded);
        }

        // Return error result
        return [
            'success' => false,
            'error' => 'Could not parse AI response as valid JSON',
            'plate' => null,
            'confidence' => 0.0,
        ];
    }

    private function validateAiResponse(array $data): array {
        $threshold = $this->config['confidence_threshold'];

        // Validate required fields
        $vehicleDetected = $data['vehicle_detected'] ?? false;
        $plateDetected = $data['plate_detected'] ?? false;

        // Normalize plate
        $plate = null;
        $confidence = 0.0;

        if ($plateDetected && !empty($data['plate_number'])) {
            $plate = normalizePlate((string)$data['plate_number']);
            $confidence = min(max((float)($data['confidence'] ?? 0.0), 0.0), 1.0);
        }

        return [
            'success' => true,
            'vehicle_detected' => $vehicleDetected,
            'plate_detected' => $plateDetected,
            'plate' => $plate,
            'confidence' => $confidence,
            'above_threshold' => $confidence >= $threshold,
            'reason' => $data['reason'] ?? '',
            'raw_response' => $data,
        ];
    }
}
