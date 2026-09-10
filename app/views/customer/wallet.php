<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-wallet text-primary"></i> Wallet</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Balance Card -->
    <div class="col-md-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h6 class="card-title text-white-50">Current Balance</h6>
                <h2 class="mb-0">RM <?php echo number_format($wallet['balance'], 2); ?></h2>
                <hr class="my-3 bg-white-50">
                <button class="btn btn-light w-100" data-bs-toggle="modal" data-bs-target="#reloadModal">
                    <i class="fas fa-plus-circle"></i> Reload Wallet
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Reload -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Reload</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-primary w-100 py-3" onclick="quickReload(10)">RM 10</button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-primary w-100 py-3" onclick="quickReload(20)">RM 20</button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-primary w-100 py-3" onclick="quickReload(50)">RM 50</button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-primary w-100 py-3" onclick="quickReload(100)">RM 100</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Transaction History</h5>
            </div>
            <div class="card-body">
                <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?php echo e(date('M d, H:i', strtotime($tx['created_at']))); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($tx['transaction_type']) {
                                        'RELOAD' => 'success',
                                        'PARKING_PAYMENT' => 'info',
                                        'COMPOUND_PAYMENT' => 'danger',
                                        'REFUND' => 'warning',
                                        default => 'secondary'
                                    }; ?>">
                                        <?php echo e($tx['transaction_type']); ?>
                                    </span>
                                </td>
                                <td class="<?php echo $tx['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($tx['amount'] > 0 ? '+' : ''); ?>RM <?php echo number_format(abs($tx['amount']), 2); ?>
                                </td>
                                <td>RM <?php echo number_format($tx['balance_after'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $tx['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo e(ucfirst($tx['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No transactions yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reload Modal -->
<div class="modal fade" id="reloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reload Wallet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reloadForm">
                    <div class="mb-3">
                        <label class="form-label">Amount (RM)</label>
                        <input type="number" class="form-control" id="reloadAmount" min="1" step="0.01" value="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod">
                            <option value="paypal">PayPal</option>
                            <option value="admin">Admin Credit (Demo)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processReload()">Pay & Reload</button>
            </div>
        </div>
    </div>
</div>
