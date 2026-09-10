<?php
/**
 * Parking Service
 * Handles parking session lifecycle
 */
declare(strict_types=1);

namespace App\Services;

class ParkingService {
    private $sessionModel;
    private $zoneModel;
    private $walletModel;
    private $notificationModel;
    private $pdo;

    public function __construct() {
        $this->sessionModel = new \App\Models\ParkingSessionModel();
        $this->zoneModel = new \App\Models\ZoneModel();
        $this->walletModel = new \App\Models\WalletModel();
        $this->notificationModel = new \App\Models\NotificationModel();
        $this->pdo = db();
    }

    /**
     * Start a new parking session
     */
    public function startSession(int $customerId, int $vehicleId, int $zoneId, ?float $gpsLat = null, ?float $gpsLng = null, ?string $plateSnapshot = null): array {
        $zone = $this->zoneModel->findById($zoneId);
        if (!$zone) {
            return ['success' => false, 'error' => 'Zone not found'];
        }

        if ($zone['status'] !== 'active') {
            return ['success' => false, 'error' => 'Zone is not active'];
        }

        // Check for existing active session
        $existing = $this->sessionModel->findByVehicle($vehicleId);
        if ($existing) {
            return ['success' => false, 'error' => 'Vehicle already has an active parking session'];
        }

        // Get vehicle details
        $vehicleModel = new \App\Models\VehicleModel();
        $vehicle = $vehicleModel->findById($vehicleId);
        if (!$vehicle || $vehicle['owner_id'] !== $customerId) {
            return ['success' => false, 'error' => 'Vehicle not found or unauthorized'];
        }

        // Calculate session times
        $now = new \DateTime('now', new \DateTimeZone(APP_TIMEZONE));
        $durationMinutes = (int)($zone['max_duration'] ?? 120);
        $endTime = clone $now;
        $endTime->modify("+{$durationMinutes} minutes");

        // Calculate fee
        $hourlyRate = (float)($zone['hourly_rate'] ?? 1.00);
        $fee = round(($durationMinutes / 60) * $hourlyRate, 2);

        // Generate session number
        $sessionNumber = 'PS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        // Check wallet balance
        $wallet = $this->walletModel->getOrCreate($customerId);
        if ((float)$wallet['balance'] < $fee) {
            return [
                'success' => false,
                'error' => "Insufficient wallet balance. Required: RM {$fee}, Available: RM {$wallet['balance']}"
            ];
        }

        // Begin transaction
        $this->pdo->beginTransaction();

        try {
            // Debit wallet
            $debitSuccess = $this->walletModel->debit(
                $customerId,
                $fee,
                $sessionNumber,
                'PARKING_PAYMENT'
            );

            if (!$debitSuccess) {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'Wallet debit failed'];
            }

            // Create parking session
            $sessionId = $this->sessionModel->create([
                'session_number' => $sessionNumber,
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'normalized_plate' => strtoupper(preg_replace('/[\s\-]+/', '', $vehicle['plate'])),
                'plate_snapshot' => $plateSnapshot,
                'zone_id' => $zoneId,
                'gps_lat' => $gpsLat,
                'gps_lng' => $gpsLng,
                'start_time' => $now->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
                'duration_minutes' => $durationMinutes,
                'fee' => $fee,
                'rate_snapshot' => json_encode(['hourly_rate' => $hourlyRate, 'max_duration' => $durationMinutes]),
                'payment_transaction_id' => $sessionNumber,
            ]);

            $this->pdo->commit();

            // Notify customer
            $this->notificationModel->create(
                $customerId,
                'parking_started',
                'Parking Started',
                "Your parking session {$sessionNumber} has started in Zone {$zone['name']}. Ends at {$endTime->format('H:i')}."
            );

            // Update zone available spaces
            $this->zoneModel->updateAvailableSpaces($zoneId, max(0, (int)$zone['available_spaces'] - 1));

            logEvent('parking', "Session started: {$sessionNumber} | Vehicle: {$vehicle['plate']} | Zone: {$zoneId} | Fee: RM {$fee}");

            return [
                'success' => true,
                'session_id' => $sessionId,
                'session_number' => $sessionNumber,
                'fee' => $fee,
                'end_time' => $endTime->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            logEvent('error', "Parking session creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create parking session'];
        }
    }

    /**
     * Extend a parking session
     */
    public function extendSession(int $sessionId, int $customerId, int $additionalMinutes = 60): array {
        $session = $this->sessionModel->findById($sessionId);
        if (!$session || $session['customer_id'] !== $customerId || $session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session not found or not active'];
        }

        $zone = $this->zoneModel->findById($session['zone_id']);
        if (!$zone) {
            return ['success' => false, 'error' => 'Zone not found'];
        }

        $hourlyRate = (float)json_decode($session['rate_snapshot'] ?? '{}', true)['hourly_rate'] ?? 1.00;
        $fee = round(($additionalMinutes / 60) * $hourlyRate, 2);

        // Check wallet
        $wallet = $this->walletModel->getOrCreate($customerId);
        if ((float)$wallet['balance'] < $fee) {
            return ['success' => false, 'error' => "Insufficient wallet balance. Required: RM {$fee}"];
        }

        // Debit wallet
        $debitSuccess = $this->walletModel->debit($customerId, $fee, "EXT-{$sessionId}-" . time(), 'PARKING_EXTENSION');
        if (!$debitSuccess) {
            return ['success' => false, 'error' => 'Wallet debit failed'];
        }

        // Extend session
        $newEndTime = date('Y-m-d H:i:s', strtotime($session['end_time'] . " +{$additionalMinutes} minutes"));
        $success = $this->sessionModel->extend($sessionId, $newEndTime, $additionalMinutes, $fee);

        if ($success) {
            $this->notificationModel->create(
                $customerId,
                'parking_extended',
                'Parking Extended',
                "Your parking session has been extended by {$additionalMinutes} minutes. New end time: {$newEndTime}."
            );
        }

        return ['success' => $success, 'new_end_time' => $newEndTime, 'fee' => $fee];
    }

    /**
     * End a parking session
     */
    public function endSession(int $sessionId, int $customerId): array {
        $session = $this->sessionModel->findById($sessionId);
        if (!$session || $session['customer_id'] !== $customerId || $session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session not found or not active'];
        }

        // Calculate actual duration and fee
        $startTime = new \DateTime($session['start_time'], new \DateTimeZone(APP_TIMEZONE));
        $now = new \DateTime('now', new \DateTimeZone(APP_TIMEZONE));
        $actualMinutes = floor($startTime->diff($now)->i + ($startTime->diff($now)->h * 60));
        $actualFee = $session['fee']; // Fee already prepaid

        $success = $this->sessionModel->updateStatus($sessionId, 'completed');

        if ($success) {
            // Update zone available spaces
            $this->zoneModel->updateAvailableSpaces($session['zone_id'], (int)$session['available_spaces'] + 1);

            $this->notificationModel->create(
                $customerId,
                'parking_ended',
                'Parking Completed',
                "Your parking session in Zone {$session['zone_id']} has ended. Duration: {$actualMinutes} minutes."
            );

            logEvent('parking', "Session ended: {$sessionId} | Duration: {$actualMinutes}min | Fee: RM {$actualFee}");
        }

        return ['success' => $success, 'actual_duration_minutes' => $actualMinutes, 'actual_fee' => $actualFee];
    }

    /**
     * Get active sessions for a zone
     */
    public function getActiveSessionsByZone(int $zoneId): array {
        return $this->sessionModel->getActiveByZone($zoneId);
    }

    /**
     * Check if a vehicle has valid parking
     */
    public function checkVehicleParking(string $normalizedPlate, ?int $zoneId = null): array {
        $session = $this->sessionModel->findByPlate($normalizedPlate);

        if (!$session) {
            return ['has_session' => false, 'session' => null, 'status' => 'no_session'];
        }

        $now = new \DateTime('now', new \DateTimeZone(APP_TIMEZONE));
        $endTime = new \DateTime($session['end_time'], new \DateTimeZone(APP_TIMEZONE));

        if ($endTime < $now) {
            // Session expired - update status
            $this->sessionModel->updateStatus($session['id'], 'expired');
            return ['has_session' => false, 'session' => $session, 'status' => 'expired'];
        }

        if ($zoneId && $session['zone_id'] != $zoneId) {
            return ['has_session' => true, 'session' => $session, 'status' => 'wrong_zone'];
        }

        return ['has_session' => true, 'session' => $session, 'status' => 'active'];
    }
}
