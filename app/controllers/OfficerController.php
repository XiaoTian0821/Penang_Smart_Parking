<?php
/**
 * Officer Controller
 */

namespace App\Controllers;

use App\Services\CameraService;
use App\Services\EnforcementService;
use App\Services\CompoundService;

class OfficerController {
    private $enforcementService;
    private $compoundService;
    private $cameraService;
    private $vehicleModel;
    private $sessionModel;
    private $zoneModel;

    public function __construct() {
        requireAuth();
        $user = currentUser();
        if (!in_array($user['role'], ['officer', 'admin', 'super_admin'])) {
            redirect('/login');
        }

        $this->enforcementService = new EnforcementService();
        $this->compoundService = new CompoundService();
        $this->cameraService = new CameraService();
        $this->vehicleModel = new \App\Models\VehicleModel();
        $this->sessionModel = new \App\Models\ParkingSessionModel();
        $this->zoneModel = new \App\Models\ZoneModel();
    }

    public function index() {
        $user = currentUser();
        $pendingCompounds = $this->compoundService->getPendingReview();
        $zones = $this->zoneModel->getActive();
        $todayDetections = (new \App\Models\AiDetectionModel())->getRecent(20);

        render('officer/dashboard', [
            'pendingCompounds' => $pendingCompounds,
            'zones' => $zones,
            'todayDetections' => $todayDetections,
        ]);
    }

    public function scan() {
        $zones = $this->zoneModel->getActive();
        render('officer/scan', ['zones' => $zones]);
    }

    public function compounds() {
        $status = $_GET['status'] ?? 'all';
        if ($status === 'all') {
            $compounds = $this->compoundService->getAll(100);
        } else {
            $compounds = $this->compoundService->getPendingReview();
        }
        render('officer/compounds', ['compounds' => $compounds, 'status' => $status]);
    }

    public function evidence() {
        // List evidence files
        $evidenceDir = EVIDENCE_PATH;
        $files = [];
        if (is_dir($evidenceDir)) {
            $iterator = new \DirectoryIterator($evidenceDir);
            foreach ($iterator as $file) {
                if ($file->isFile() && !$file->isDot()) {
                    $files[] = [
                        'filename' => $file->getFilename(),
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime(),
                    ];
                }
            }
        }
        render('officer/evidence', ['evidence' => $files]);
    }
}
