<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-gavel text-primary"></i> Appeals</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($appeals) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Compound #</th>
                                <th>Customer</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appeals as $appeal): ?>
                            <tr>
                                <td><?php echo e($appeal['id']); ?></td>
                                <td><?php echo e($appeal['compound_id']); ?></td>
                                <td><?php echo e($appeal['customer_id']); ?></td>
                                <td><?php echo e(substr($appeal['reason'], 0, 100)) . (strlen($appeal['reason']) > 100 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($appeal['status']) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning'
                                    }; ?>">
                                        <?php echo e(ucfirst($appeal['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e(date('M d, H:i', strtotime($appeal['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="reviewAppeal(<?php echo $appeal['id']; ?>, 'approved')">Approve</button>
                                    <button class="btn btn-sm btn-danger" onclick="reviewAppeal(<?php echo $appeal['id']; ?>, 'rejected')">Reject</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No appeals found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
