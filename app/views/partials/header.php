<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-url" content="<?php echo e(APP_URL); ?>">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/main.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/main.css'); ?>" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (hasRole('customer')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/customer"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/customer/vehicles"><i class="fas fa-car"></i> Vehicles</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/customer/parking"><i class="fas fa-parking"></i> Parking</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/customer/wallet"><i class="fas fa-wallet"></i> Wallet</a></li>
                    <?php endif; ?>
                    <?php if (hasRole('officer') || hasRole('admin') || hasRole('super_admin')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/officer/scan"><i class="fas fa-camera"></i> Scan</a></li>
                    <?php endif; ?>
                    <?php if (hasRole('admin') || hasRole('super_admin')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>/admin"><i class="fas fa-cog"></i> Admin</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo e(currentUser()['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/customer/profile"><i class="fas fa-user-circle"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/customer/notifications"><i class="fas fa-bell"></i> Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="py-4">
        <div class="container">
            <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo e($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo e($_SESSION['flash_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
