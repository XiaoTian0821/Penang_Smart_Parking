<?php
/**
 * Admin Controller
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\VehicleModel;
use App\Models\ParkingSessionModel;
use App\Models\CompoundModel;
use App\Services\CompoundService;
use App\Models\AppealModel;
use App\Models\CameraModel;
use App\Models\AiDetectionModel;

class AdminController {
    private $userModel;
    private $zoneModel;
    private $vehicleModel;
    private $sessionModel;
    private $compoundService;
    private $appealModel;
    private $cameraModel;
    private $aiDetectionModel;

    public function __construct() {
        requireAuth();
        $user = currentUser();
        if (!in_array($user['role'], ['admin', 'super_admin'])) {
            redirect('/login');
        }

        $this->userModel = new UserModel();
        $this->zoneModel = new \App\Models\ZoneModel();
        $this->vehicleModel = new VehicleModel();
        $this->sessionModel = new ParkingSessionModel();
        $this->compoundService = new CompoundService();
        $this->appealModel = new AppealModel();
        $this->cameraModel = new CameraModel();
        $this->aiDetectionModel = new AiDetectionModel();
    }

    public function index() {
        $totalUsers = $this->userModel->count();
        $totalSessions = $this->sessionModel->count();
        $totalCompounds = (new CompoundModel())->count();
        $pendingCompounds = $this->compoundService->getPendingReview();
        $recentDetections = $this->aiDetectionModel->getRecent(10);
        $zones = $this->zoneModel->getActive();

        $todayRevenue = 0;
        $monthRevenue = 0;

        render('admin/dashboard', [
            'totalUsers' => $totalUsers,
            'totalSessions' => $totalSessions,
            'totalCompounds' => $totalCompounds,
            'pendingCompounds' => $pendingCompounds,
            'recentDetections' => $recentDetections,
            'zones' => $zones,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
        ]);
    }

    public function users() {
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $users = $this->userModel->getAll($limit, $offset);
        render('admin/users', ['users' => $users]);
    }

    public function zones() {
        $zones = $this->zoneModel->getAll();
        render('admin/zones', ['zones' => $zones]);
    }

    public function vehicles() {
        $search = $_GET['search'] ?? '';
        if ($search) {
            $vehicles = $this->vehicleModel->search($search);
        } else {
            $vehicles = [];
        }
        render('admin/vehicles', ['vehicles' => $vehicles]);
    }

    public function sessions() {
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $sessions = $this->sessionModel->getCustomerSessions(1, 0);
        render('admin/sessions', ['sessions' => $sessions]);
    }

    public function compounds() {
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $compounds = $this->compoundService->getAll($limit, $offset);
        render('admin/compounds', ['compounds' => $compounds]);
    }

    public function appeals() {
        $appeals = $this->appealModel->getAll(100);
        render('admin/appeals', ['appeals' => $appeals]);
    }

    public function cameras() {
        $cameras = $this->cameraModel->getAll();
        render('admin/cameras', ['cameras' => $cameras]);
    }

    public function reports() {
        $zones = $this->zoneModel->getActive();
        render('admin/reports', ['zones' => $zones]);
    }

    public function audit() {
        $logFile = LOGS_PATH . '/' . date('Y-m') . '.log';
        $logs = [];
        if (file_exists($logFile)) {
            $logs = array_reverse(array_slice(explode("\n", trim(file_get_contents($logFile))), 0, 500));
        }
        render('admin/audit', ['logs' => $logs]);
    }

    public function settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['flash_message'] = 'Settings updated';
            $_SESSION['flash_type'] = 'success';
            redirect('/admin/settings');
        }
        render('admin/settings');
    }
}
