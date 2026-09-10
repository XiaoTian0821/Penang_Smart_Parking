<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-gavel text-primary"></i> Compounds</h2>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <select class="form-select" id="statusFilter" onchange="filterCompounds()">
            <option value="all">All Status</option>
            <option value="pending_review">Pending Review</option>
            <option value="issued">Issued</option>
            <option value="paid">Paid</option>
            <option value="appealed">Appealed</option>
            <option value="cancelled">Cancelled</option>
        </select>
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
                                <th>Zone</th>
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
                                <td><?php echo e($compound['zone_id']); ?></td>
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
                                    <?php if ($compound['status'] === 'pending_review'): ?>
                                    <button class="btn btn-sm btn-success" onclick="reviewCompound(<?php echo $compound['id']; ?>, 'issued')">
                                        <i class="fas fa-check"></i> Issue
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="reviewCompound(<?php echo $compound['id']; ?>, 'rejected')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <?php elseif ($compound['status'] === 'issued'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="viewEvidence(<?php echo $compound['id']; ?>)">
                                        <i class="fas fa-image"></i> Evidence
                                    </button>
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
