<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-history text-primary"></i> Parking History</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($sessions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Session #</th>
                                <th>Plate</th>
                                <th>Zone</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Duration</th>
                                <th>Fee</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><strong><?php echo e($session['session_number']); ?></strong></td>
                                <td><?php echo e($session['normalized_plate']); ?></td>
                                <td><?php echo e($session['zone_id']); ?></td>
                                <td><?php echo e(date('M d, H:i', strtotime($session['start_time']))); ?></td>
                                <td><?php echo e(date('M d, H:i', strtotime($session['end_time']))); ?></td>
                                <td><?php echo e($session['duration_minutes']); ?> min</td>
                                <td>RM <?php echo number_format($session['fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($session['status']) {
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'expired' => 'warning',
                                        'cancelled' => 'secondary',
                                        default => 'dark'
                                    }; ?>">
                                        <?php echo e(ucfirst($session['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clock text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No parking history yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
