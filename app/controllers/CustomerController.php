<?php
/**
 * Customer Dashboard Controller
 */

class CustomerController {
    private $parkingService;
    private $walletService;
    private $vehicleModel;
    private $sessionModel;
    private $compoundModel;
    private $notificationService;

    public function __construct() {
        requireAuth();
        $user = currentUser();
        if ($user['role'] !== 'customer') {
            redirect('/login');
        }

        $this->parkingService = new \App\Services\ParkingService();
        $this->walletService = new WalletService();
        $this->vehicleModel = new VehicleModel();
        $this->sessionModel = new ParkingSessionModel();
        $this->compoundModel = new CompoundModel();
        $this->notificationService = new NotificationService();
    }

    public function index() {
        $user = currentUser();
        $vehicles = $this->vehicleModel->findByOwner($user['id']);
        $activeSession = $this->sessionModel->findByVehicle($vehicles[0]['id'] ?? null);
        $wallet = $this->walletService->getWallet($user['id']);
        $unreadNotifications = $this->notificationService->getUnread($user['id']);

        $recentSessions = $this->sessionModel->getCustomerSessions($user['id'], 5);

        $pendingCompounds = [];
        foreach ($vehicles as $vehicle) {
            $compounds = $this->compoundModel->findByPlate($vehicle['normalized_plate']);
            foreach ($compounds as $compound) {
                if (in_array($compound['status'], ['issued', 'pending_review'])) {
                    $pendingCompounds[] = $compound;
                }
            }
        }

        render('customer/dashboard', [
            'vehicles' => $vehicles,
            'activeSession' => $activeSession,
            'wallet' => $wallet,
            'unreadNotifications' => $unreadNotifications,
            'recentSessions' => $recentSessions,
            'pendingCompounds' => $pendingCompounds,
        ]);
    }

    public function vehicles() {
        $user = currentUser();
        $vehicles = $this->vehicleModel->findByOwner($user['id']);
        render('customer/vehicles', ['vehicles' => $vehicles]);
    }

    public function parking() {
        $user = currentUser();
        $vehicles = $this->vehicleModel->findByOwner($user['id']);
        $activeSession = null;
        foreach ($vehicles as $vehicle) {
            $session = $this->sessionModel->findByVehicle($vehicle['id']);
            if ($session) {
                $activeSession = $session;
                break;
            }
        }
        $zones = (new \App\Models\ZoneModel())->getActive();
        render('customer/parking', [
            'vehicles' => $vehicles,
            'activeSession' => $activeSession,
            'zones' => $zones,
        ]);
    }

    public function wallet() {
        $user = currentUser();
        $wallet = $this->walletService->getWallet($user['id']);
        $transactions = $this->walletService->getTransactions($user['id'], 50);
        render('customer/wallet', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    public function compounds() {
        $user = currentUser();
        $vehicles = $this->vehicleModel->findByOwner($user['id']);
        $compounds = [];
        foreach ($vehicles as $vehicle) {
            $compounds = array_merge($compounds, $this->compoundModel->findByPlate($vehicle['normalized_plate']));
        }
        render('customer/compounds', ['compounds' => $compounds]);
    }

    public function history() {
        $user = currentUser();
        $sessions = $this->sessionModel->getCustomerSessions($user['id'], 100);
        render('customer/history', ['sessions' => $sessions]);
    }

    public function notifications() {
        $user = currentUser();
        $notifications = $this->notificationService->getRecent($user['id'], 50);
        $this->notificationService->markAllRead($user['id']);
        render('customer/notifications', ['notifications' => $notifications]);
    }

    public function profile() {
        $user = currentUser();
        $userModel = new UserModel();
        $userData = $userModel->findById($user['id']);
        render('customer/profile', ['user' => $userData]);
    }
}
