<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-car text-primary"></i> Vehicle Search</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search" placeholder="Search by plate number..." value="<?php echo e($_GET['search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (count($vehicles) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Plate</th><th>Type</th><th>Color</th><th>Owner</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td><strong><?php echo e($v['plate']); ?></strong></td>
                                <td><?php echo e(ucfirst($v['vehicle_type'])); ?></td>
                                <td><?php echo e($v['color'] ?? '-'); ?></td>
                                <td><?php echo e($v['owner_id']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View Sessions</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No vehicles found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
