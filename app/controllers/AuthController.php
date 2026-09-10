<?php
/**
 * Auth Controller
 */

namespace App\Controllers;

use App\Models\UserModel;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }
        render('auth/login', ['csrf_token' => csrf_token()]);
    }

    private function handleLogin(): void {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!csrf_verify($csrf_token)) {
            $_SESSION['flash_message'] = 'Invalid CSRF token';
            $_SESSION['flash_type'] = 'error';
            redirect('/login');
        }

        if (!checkRateLimit('login_' . $email)) {
            $_SESSION['flash_message'] = 'Too many login attempts. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('/login');
        }

        if (empty($email) || empty($password)) {
            $_SESSION['flash_message'] = 'Please enter email and password';
            $_SESSION['flash_type'] = 'error';
            redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !verifyPassword($password, $user['password'])) {
            $_SESSION['flash_message'] = 'Invalid email or password';
            $_SESSION['flash_type'] = 'error';
            redirect('/login');
        }

        if ($user['status'] !== 'active') {
            $_SESSION['flash_message'] = 'Account is deactivated. Contact administrator.';
            $_SESSION['flash_type'] = 'error';
            redirect('/login');
        }

        if (loginUser($user['id'])) {
            $redirect = match ($user['role']) {
                'super_admin', 'admin' => '/admin',
                'officer' => '/officer',
                default => '/customer',
            };
            redirect($redirect, 'Welcome back, ' . $user['full_name'] . '!', 'success');
        }

        $_SESSION['flash_message'] = 'Login failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        redirect('/login');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
            return;
        }
        render('auth/register', ['csrf_token' => csrf_token()]);
    }

    private function handleRegister(): void {
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (!csrf_verify($csrf_token)) {
            $_SESSION['flash_message'] = 'Invalid CSRF token';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $icNumber = trim($_POST['ic_number'] ?? '');

        if (empty($email) || empty($password) || empty($fullName)) {
            $_SESSION['flash_message'] = 'All required fields must be filled';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        if (!validateEmail($email)) {
            $_SESSION['flash_message'] = 'Invalid email address';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_message'] = 'Password must be at least 8 characters';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        if ($password !== $confirmPassword) {
            $_SESSION['flash_message'] = 'Passwords do not match';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        if ($icNumber && !validateIC($icNumber)) {
            $_SESSION['flash_message'] = 'Invalid IC number format';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        $existing = $this->userModel->findByEmail($email);
        if ($existing) {
            $_SESSION['flash_message'] = 'Email already registered';
            $_SESSION['flash_type'] = 'error';
            redirect('/register');
        }

        $userId = $this->userModel->create([
            'email' => $email,
            'password' => hashPassword($password),
            'full_name' => $fullName,
            'phone' => $phone,
            'ic_number' => $icNumber,
            'role' => 'customer',
            'status' => 'active',
            'permissions' => json_encode([]),
        ]);

        if ($userId) {
            logEvent('auth', "New user registered: ID {$userId} Email: {$email}");
            redirect('/login', 'Registration successful! Please login.', 'success');
        }

        $_SESSION['flash_message'] = 'Registration failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        redirect('/register');
    }

    public function logout() {
        logoutUser();
        redirect('/login', 'You have been logged out.', 'info');
    }
}
