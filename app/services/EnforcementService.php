<?php
/**
 * Enforcement Service
 * Main enforcement workflow: AI detection -> Rule engine -> Violation determination
 */
declare(strict_types=1);

namespace App\Services;

use App\Models\AiDetectionModel;
use App\Models\NotificationModel;
use App\Models\VehicleModel;
use App\Models\ZoneModel;
use \CameraService;
use \GeminiService;
use App\Services\CompoundService;
use App\Services\ParkingService;

class EnforcementService {
    private $geminiService;
    private $cameraService;
    private $parkingService;
    private $compoundService;
    private $enforcementModel;
    private $aiDetectionModel;
    private $zoneModel;
    private $vehicleModel;
    private $notificationModel;

    public function __construct() {
        $this->geminiService = new GeminiService();
        $this->cameraService = new CameraService();
        $this->parkingService = new ParkingService();
        $this->compoundService = new CompoundService();
        $this->zoneModel = new ZoneModel();
        $this->vehicleModel = new VehicleModel();
        $this->notificationModel = new NotificationModel();
        $this->aiDetectionModel = new AiDetectionModel();
    }

    /**
     * Process a vehicle detection from an image
     * This is the main enforcement entry point
     */
    public function processDetection(array $imageData, ?int $zoneId = null, ?int $officerId = null): array {
        // 1. Save image
        $saveResult = $this->cameraService->saveBase64($imageData['base64'], $imageData['mime'] ?? 'image/jpeg');
        if (!$saveResult['success']) {
            return ['success' => false, 'error' => $saveResult['error']];
        }

        $imagePath = $saveResult['path'];
        $filename = $saveResult['filename'];

        // 2. AI Plate Recognition
        $aiResult = $this->geminiService->recognizePlate($imagePath);

        // 3. Record AI detection
        $detectionId = $this->aiDetectionModel->create([
            'plate' => $aiResult['plate'] ?? null,
            'confidence' => $aiResult['confidence'] ?? 0.0,
            'model_used' => $aiResult['model'] ?? 'unknown',
            'processing_time' => $aiResult['processing_time'] ?? 0,
            'image_path' => $imagePath,
            'status' => $aiResult['success'] ? 'processed' : 'error',
            'enforcement_result' => null,
        ]);

        // 4. If plate not detected or low confidence, return for manual review
        $plateDetected = $aiResult['plate_detected'] ?? false;
        $aboveThreshold = $aiResult['above_threshold'] ?? false;

        if (!$aiResult['success'] || !$plateDetected || !$aboveThreshold) {
            return [
                'success' => true,
                'detection_id' => $detectionId,
                'plate' => null,
                'confidence' => $aiResult['confidence'] ?? 0.0,
                'status' => 'review',
                'message' => $plateDetected
                    ? 'Confidence below threshold. Requires manual review.'
                    : 'No plate detected. Requires manual review.',
                'needs_review' => true,
                'image_path' => $imagePath,
            ];
        }

        $plate = $aiResult['plate'];

        // 5. Check parking status via rule engine
        $parkingCheck = $this->parkingService->checkVehicleParking($plate, $zoneId);
        $violation = $this->evaluateViolation($plate, $parkingCheck, $zoneId);

        // 6. Update detection with result
        $this->aiDetectionModel->create([
            'plate' => $plate,
            'confidence' => $aiResult['confidence'],
            'model_used' => $aiResult['model'],
            'processing_time' => $aiResult['processing_time'],
            'image_path' => $imagePath,
            'status' => $violation ? 'violation' : 'clean',
            'enforcement_result' => $violation,
        ]);

        // 7. If violation detected, create compound for review
        $result = [
            'success' => true,
            'detection_id' => $detectionId,
            'plate' => $plate,
            'confidence' => $aiResult['confidence'],
            'parking_status' => $parkingCheck['status'],
            'violation' => $violation,
            'status' => $violation ? 'violation' : 'valid',
            'needs_review' => false,
            'image_path' => $imagePath,
        ];

        if ($violation && $officerId) {
            // Create compound - status will be pending_review or issued based on zone config
            $compoundData = [
                'violation_type' => $violation,
                'normalized_plate' => $plate,
                'plate_snapshot' => $imagePath,
                'zone_id' => $zoneId,
                'detection_time' => date('Y-m-d H:i:s'),
                'evidence_path' => $imagePath,
                'amount' => $this->getViolationAmount($violation, $zoneId),
                'status' => 'pending_review',
                'issued_by' => $officerId,
                'ai_detection_id' => $detectionId,
            ];

            // Try to find vehicle/customer
            $vehicle = $this->vehicleModel->findByPlate($plate);
            if ($vehicle) {
                $compoundData['vehicle_id'] = $vehicle['id'];
                $compoundData['customer_id'] = $vehicle['owner_id'];
            }

            $compoundId = $this->compoundService->createCompound($compoundData);
            $result['compound_id'] = $compoundId;

            // Notify customer if found
            if ($vehicle['owner_id']) {
                $this->notificationModel->create(
                    $vehicle['owner_id'],
                    'violation_detected',
                    'Parking Violation Detected',
                    "A parking violation has been detected for plate {$plate}. Type: {$violation}. A compound will be issued after review."
                );
            }
        }

        logEvent('enforcement', "Detection: {$detectionId} | Plate: {$plate} | Violation: " . ($violation ?: 'none') . " | Zone: {$zoneId}");

        return $result;
    }

    /**
     * Evaluate parking violation using rule engine
     */
    private function evaluateViolation(string $plate, array $parkingCheck, ?int $zoneId): ?string {
        // Rule A: No valid parking session
        if (!$parkingCheck['has_session']) {
            return 'PARKING_NOT_PAID';
        }

        $session = $parkingCheck['session'];
        $zone = $zoneId ? $this->zoneModel->findById($zoneId) : null;

        // Rule B: Session expired (shouldn't reach here if checkVehicleParking works, but safety check)
        $now = new \DateTime('now', new \DateTimeZone(APP_TIMEZONE));
        $endTime = new \DateTime($session['end_time'], new \DateTimeZone(APP_TIMEZONE));
        if ($endTime <= $now) {
            return 'PARKING_EXPIRED';
        }

        // Rule C: Wrong zone (skip for open/GPS parking)
        if ($zoneId && $session['zone_id'] != $zoneId) {
            // Check if it's an open GPS parking session
            $zoneData = json_decode($session['rate_snapshot'] ?? '{}', true);
            if (!isset($zoneData['open_parking']) || !$zoneData['open_parking']) {
                return 'WRONG_PARKING_ZONE';
            }
        }

        // Rule D: Outside operating hours
        if ($zone && $zone['enforce_outside_hours']) {
            $hours = json_decode($zone['operating_hours'] ?? '{}', true);
            if ($hours && !$this->isWithinOperatingHours($hours)) {
                return 'OUTSIDE_OPERATING_HOURS';
            }
        }

        // Rule E: Maximum duration exceeded
        $startTime = new \DateTime($session['start_time'], new \DateTimeZone(APP_TIMEZONE));
        $duration = floor($startTime->diff($now)->i + ($startTime->diff($now)->h * 60));
        $maxDuration = $session['duration_minutes'];
        if ($duration > $maxDuration) {
            return 'MAXIMUM_DURATION_EXCEEDED';
        }

        return null; // No violation
    }

    private function isWithinOperatingHours(array $hours): bool {
        $now = new \DateTime('now', new \DateTimeZone(APP_TIMEZONE));
        $dayOfWeek = $now->format('l');
        $time = $now->format('H:i');

        // Check if it's a public holiday
        $holidays = json_decode('{
            "2026-01-01": "New Year",
            "2026-01-29": "Chinese New Year",
            "2026-01-30": "Chinese New Year Day 2",
            "2026-02-17": "Hari Raya Puasa",
            "2026-04-10": "Good Friday",
            "2026-04-18": "Hari Raya Haji",
            "2026-05-01": "Labour Day",
            "2026-05-12": "Wesak Day",
            "2026-05-22": "Princessugual Birthday",
            "2026-06-06": "Heroes Day",
            "2026-06-07": "Hari Raya Haji",
            "2026-08-12": "Hari Raya Puasa",
            "2026-08-31": "Merdeka",
            "2026-09-15": "Hari Malaysia",
            "2026-10-15": "Deepavali",
            "2026-12-25": "Christmas"
        }', true);

        $todayStr = $now->format('Y-m-d');
        if (isset($holidays[$todayStr])) {
            // On public holiday, use Sunday hours as default
            $dayOfWeek = 'Sunday';
        }

        $dayHours = $hours[strtolower($dayOfWeek)] ?? $hours['sunday'] ?? null;
        if (!$dayHours) {
            return false;
        }

        return $time >= $dayHours['start'] && $time <= $dayHours['end'];
    }

    private function getViolationAmount(string $violation, ?int $zoneId): float {
        // Default amounts - can be configured per zone
        $amounts = [
            'PARKING_NOT_PAID' => 10.00,
            'PARKING_EXPIRED' => 10.00,
            'WRONG_PARKING_ZONE' => 20.00,
            'OUTSIDE_OPERATING_HOURS' => 10.00,
            'MAXIMUM_DURATION_EXCEEDED' => 10.00,
        ];
        return $amounts[$violation] ?? 10.00;
    }
}
