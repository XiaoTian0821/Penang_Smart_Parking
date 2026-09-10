<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-search text-primary"></i> Audit Log</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($logs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>IP</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice(array_reverse($logs), 0, 100) as $log): ?>
                            <?php if (trim($log)): ?>
                            <tr>
                                <?php
                                $parts = explode(']', substr($log, 1));
                                $timestamp = $parts[0] ?? '';
                                $type = $parts[1] ?? '';
                                $user = $parts[2] ?? '';
                                $rest = implode(']', array_slice($parts, 3));
                                ?>
                                <td><?php echo e($timestamp); ?></td>
                                <td><span class="badge bg-secondary"><?php echo e(trim($type)); ?></span></td>
                                <td><?php echo e(trim($user)); ?></td>
                                <td><?php
                                    if (preg_match('/IP:([^\]]+)/', $rest, $m)) echo e($m[1]);
                                ?></td>
                                <td class="text-muted small"><?php echo e(preg_replace('/\[.*?\]/', '', $rest)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No audit logs found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
