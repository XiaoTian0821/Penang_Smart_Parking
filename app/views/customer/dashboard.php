<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-home text-primary"></i> Dashboard</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Wallet Balance Card -->
    <div class="col-md-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Wallet Balance</h6>
                        <h2 class="mb-0">RM <?php echo number_format($wallet['balance'], 2); ?></h2>
                    </div>
                    <i class="fas fa-wallet fa-3x text-white-50"></i>
                </div>
                <a href="<?php echo APP_URL; ?>/customer/wallet" class="btn btn-light btn-sm mt-3">Manage Wallet</a>
            </div>
        </div>
    </div>

    <!-- Active Parking Session -->
    <div class="col-md-4">
        <div class="card h-100 <?php echo $activeSession ? 'border-success' : ''; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Active Session</h6>
                        <?php if ($activeSession): ?>
                            <h5 class="text-success mb-0">Active</h5>
                            <small>Zone: <?php echo $activeSession['zone_id']; ?></small>
                            <br><small>Ends: <?php echo date('H:i', strtotime($activeSession['end_time'])); ?></small>
                        <?php else: ?>
                            <h5 class="text-muted mb-0">None</h5>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-parking fa-3x <?php echo $activeSession ? 'text-success' : 'text-muted'; ?>"></i>
                </div>
                <?php if (!$activeSession): ?>
                <a href="<?php echo APP_URL; ?>/customer/parking" class="btn btn-primary btn-sm mt-3">Start Parking</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending Compounds -->
    <div class="col-md-4">
        <div class="card h-100 <?php echo count($pendingCompounds) > 0 ? 'border-danger' : ''; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted">Pending Compounds</h6>
                        <?php if (count($pendingCompounds) > 0): ?>
                            <h5 class="text-danger mb-0"><?php echo count($pendingCompounds); ?></h5>
                            <small>Review required</small>
                        <?php else: ?>
                            <h5 class="text-success mb-0">0</h5>
                            <small class="text-success">All clear</small>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x <?php echo count($pendingCompounds) > 0 ? 'text-danger' : 'text-success'; ?>"></i>
                </div>
                <?php if (count($pendingCompounds) > 0): ?>
                <a href="<?php echo APP_URL; ?>/customer/compounds" class="btn btn-danger btn-sm mt-3">View Compounds</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Vehicles -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-car"></i> My Vehicles</h5>
                <a href="<?php echo APP_URL; ?>/customer/vehicles" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="card-body">
                <?php if (count($vehicles) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Type</th>
                                <th>Color</th>
                                <th>Make/Model</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><strong><?php echo e($vehicle['plate']); ?></strong></td>
                                <td><?php echo e(ucfirst($vehicle['vehicle_type'])); ?></td>
                                <td><?php echo e($vehicle['color'] ?? '-'); ?></td>
                                <td><?php echo e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')); ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No vehicles registered. <a href="<?php echo APP_URL; ?>/customer/vehicles">Add a vehicle</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Parking</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentSessions) > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($recentSessions, 0, 5) as $session): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <div>
                            <strong><?php echo e($session['session_number']); ?></strong><br>
                            <small class="text-muted">Zone <?php echo e($session['zone_id']); ?> | <?php echo e(date('M d, H:i', strtotime($session['start_time']))); ?></small>
                        </div>
                        <span class="badge bg-<?php echo $session['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo e(ucfirst($session['status'])); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">No parking sessions yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
