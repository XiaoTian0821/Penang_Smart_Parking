<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-video text-primary"></i> Camera Management</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Camera List</h5>
                <button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Camera</button>
            </div>
            <div class="card-body">
                <?php if (count($cameras) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Zone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cameras as $camera): ?>
                            <tr>
                                <td><?php echo e($camera['id']); ?></td>
                                <td><?php echo e($camera['name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo e($camera['code']); ?></span></td>
                                <td><?php echo e(ucfirst($camera['camera_type'])); ?></td>
                                <td><?php echo e($camera['zone_id'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $camera['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo e(ucfirst($camera['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No cameras configured. Cameras are used for reference - actual detection is via officer mobile devices.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
