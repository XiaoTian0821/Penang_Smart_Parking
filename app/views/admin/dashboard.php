<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt text-primary"></i> Admin Dashboard</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title text-white-50">Total Users</h6>
                <h2><?php echo e($totalUsers); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title text-white-50">Parking Sessions</h6>
                <h2><?php echo e($totalSessions); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title text-white-50">Compounds</h6>
                <h2><?php echo e($totalCompounds); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h6 class="card-title text-white-50">Pending Review</h6>
                <h2><?php echo e(count($pendingCompounds)); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Parking Zones</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Zone</th><th>Code</th><th>Rate</th><th>Available</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zones as $zone): ?>
                        <tr>
                            <td><?php echo e($zone['name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo e($zone['code']); ?></span></td>
                            <td>RM <?php echo number_format($zone['hourly_rate'], 2); ?></td>
                            <td><?php echo e($zone['available_spaces']); ?>/<?php echo e($zone['capacity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Detections</h5>
            </div>
            <div class="card-body">
                <?php if (count($recentDetections) > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($recentDetections, 0, 5) as $det): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?php echo e($det['plate'] ?? 'N/A'); ?></span>
                        <span class="badge bg-<?php echo $det['status'] === 'violation' ? 'danger' : 'success'; ?>">
                            <?php echo e(ucfirst($det['status'])); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">No recent detections.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending Compounds -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pending Compound Review</h5>
                <a href="<?php echo APP_URL; ?>/admin/compounds" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($pendingCompounds) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Compound #</th><th>Violation</th><th>Plate</th><th>Amount</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pendingCompounds, 0, 5) as $compound): ?>
                            <tr>
                                <td><?php echo e($compound['compound_number']); ?></td>
                                <td><?php echo e(str_replace('_', ' ', $compound['violation_type'])); ?></td>
                                <td><?php echo e($compound['normalized_plate']); ?></td>
                                <td>RM <?php echo number_format($compound['amount'], 2); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="adminReview(<?php echo $compound['id']; ?>, 'issued')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="adminReview(<?php echo $compound['id']; ?>, 'rejected')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No pending compounds.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
