<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-file-invoice text-primary"></i> Compounds</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($compounds) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Compound #</th>
                                <th>Violation</th>
                                <th>Plate</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compounds as $compound): ?>
                            <tr>
                                <td><strong><?php echo e($compound['compound_number']); ?></strong></td>
                                <td><?php echo e(str_replace('_', ' ', $compound['violation_type'])); ?></td>
                                <td><?php echo e($compound['normalized_plate']); ?></td>
                                <td><?php echo e($compound['customer_id'] ?? 'N/A'); ?></td>
                                <td>RM <?php echo number_format($compound['amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($compound['status']) {
                                        'issued' => 'danger',
                                        'paid' => 'success',
                                        'pending_review' => 'warning',
                                        'appealed' => 'info',
                                        'cancelled' => 'secondary',
                                        default => 'dark'
                                    }; ?>">
                                        <?php echo e(ucfirst(str_replace('_', ' ', $compound['status']))); ?>
                                    </span>
                                </td>
                                <td><?php echo e(date('M d, Y', strtotime($compound['due_date']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye"></i></button>
                                    <?php if ($compound['status'] === 'pending_review'): ?>
                                    <button class="btn btn-sm btn-success" onclick="adminReview(<?php echo $compound['id']; ?>, 'issued')">Issue</button>
                                    <button class="btn btn-sm btn-danger" onclick="adminReview(<?php echo $compound['id']; ?>, 'rejected')">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No compounds found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
