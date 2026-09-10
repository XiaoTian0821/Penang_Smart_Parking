<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-users text-primary"></i> User Management</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['id']); ?></td>
                                <td><?php echo e($user['full_name']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($user['role']) {
                                        'super_admin' => 'dark',
                                        'admin' => 'primary',
                                        'officer' => 'warning',
                                        default => 'secondary'
                                    }; ?>">
                                        <?php echo e(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo e(ucfirst($user['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e(date('M d, Y', strtotime($user['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['role'] !== 'super_admin'): ?>
                                    <button class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                        <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
