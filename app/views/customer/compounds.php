<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-file-invoice text-danger"></i> My Compounds</h2>
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
                                    <?php if ($compound['status'] === 'issued'): ?>
                                    <button class="btn btn-sm btn-success" onclick="payCompound(<?php echo $compound['id']; ?>, <?php echo $compound['amount']; ?>)">
                                        <i class="fas fa-pay"></i> Pay
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#appealModal<?php echo $compound['id']; ?>">
                                        <i class="fas fa-gavel"></i> Appeal
                                    </button>
                                    <?php elseif ($compound['status'] === 'pending_review'): ?>
                                    <span class="text-muted">Under Review</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <p class="text-muted">No compounds found. You're all clear!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
