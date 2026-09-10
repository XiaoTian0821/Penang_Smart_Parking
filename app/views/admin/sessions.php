<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-parking text-primary"></i> Parking Sessions</h2>
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
                            <tr><th>Session #</th><th>Customer</th><th>Plate</th><th>Zone</th><th>Start</th><th>End</th><th>Fee</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($sessions, 0, 100) as $s): ?>
                            <tr>
                                <td><?php echo e($s['session_number']); ?></td>
                                <td><?php echo e($s['customer_id']); ?></td>
                                <td><strong><?php echo e($s['normalized_plate']); ?></strong></td>
                                <td><?php echo e($s['zone_id']); ?></td>
                                <td><?php echo e(date('M d, H:i', strtotime($s['start_time']))); ?></td>
                                <td><?php echo e(date('M d, H:i', strtotime($s['end_time']))); ?></td>
                                <td>RM <?php echo number_format($s['fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($s['status']) {
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'expired' => 'warning',
                                        'cancelled' => 'secondary',
                                        default => 'dark'
                                    }; ?>">
                                        <?php echo e(ucfirst($s['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No sessions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
