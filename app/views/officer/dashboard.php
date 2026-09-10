<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-tachometer-alt text-primary"></i> Officer Dashboard</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                <h5>Scan Vehicle</h5>
                <p class="text-muted">Take a photo to detect plate and check parking status</p>
                <a href="<?php echo APP_URL; ?>/officer/scan" class="btn btn-primary">
                    <i class="fas fa-camera"></i> Start Scan
                </a>
            </div>
        </div>
    </div>

    <!-- Pending Compounds -->
    <div class="col-md-4">
        <div class="card text-center <?php echo count($pendingCompounds) > 0 ? 'border-warning' : ''; ?>">
            <div class="card-body">
                <i class="fas fa-gavel fa-3x <?php echo count($pendingCompounds) > 0 ? 'text-warning' : 'text-success'; ?> mb-3"></i>
                <h5>Pending Review</h5>
                <h2 class="<?php echo count($pendingCompounds) > 0 ? 'text-warning' : 'text-success'; ?>"><?php echo count($pendingCompounds); ?></h2>
                <a href="<?php echo APP_URL; ?>/officer/compounds" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> View All
                </a>
            </div>
        </div>
    </div>

    <!-- Zones -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-map-marked-alt fa-3x text-info mb-3"></i>
                <h5>Active Zones</h5>
                <h2 class="text-info"><?php echo count($zones); ?></h2>
                <a href="<?php echo APP_URL; ?>/map" class="btn btn-outline-info">
                    <i class="fas fa-map"></i> View Map
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Detections -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-camera-retro"></i> Recent AI Detections</h5>
            </div>
            <div class="card-body">
                <?php if (count($todayDetections) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Plate</th>
                                <th>Confidence</th>
                                <th>Status</th>
                                <th>Violation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayDetections as $det): ?>
                            <tr>
                                <td><?php echo e(date('H:i', strtotime($det['created_at']))); ?></td>
                                <td><strong><?php echo e($det['plate'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo e(number_format($det['confidence'] ?? 0, 2)); ?>%</td>
                                <td>
                                    <span class="badge bg-<?php echo match($det['status']) {
                                        'processed' => 'success',
                                        'review' => 'warning',
                                        'violation' => 'danger',
                                        'error' => 'secondary',
                                        default => 'dark'
                                    }; ?>">
                                        <?php echo e(ucfirst($det['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e($det['enforcement_result'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No detections today.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
