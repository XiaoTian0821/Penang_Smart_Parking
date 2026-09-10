<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-bell text-primary"></i> Notifications</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                    <div class="list-group-item <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <?php if (!$notif['is_read']): ?><i class="fas fa-circle text-primary fa-sm"></i> <?php endif; ?>
                                <?php echo e($notif['title']); ?>
                            </h6>
                            <small class="text-muted"><?php echo e(time_ago($notif['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo e($notif['message']); ?></p>
                        <small class="text-muted">
                            <span class="badge bg-secondary"><?php echo e(ucfirst($notif['type'])); ?></span>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No notifications.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
