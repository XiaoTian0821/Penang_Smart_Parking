<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-map-marker-alt text-primary"></i> Parking Zones</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Zone List</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addZoneModal">
                    <i class="fas fa-plus"></i> Add Zone
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Hourly Rate</th>
                                <th>Max Duration</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zones as $zone): ?>
                            <tr>
                                <td><?php echo e($zone['id']); ?></td>
                                <td><?php echo e($zone['name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo e($zone['code']); ?></span></td>
                                <td>RM <?php echo number_format($zone['hourly_rate'], 2); ?></td>
                                <td><?php echo e($zone['max_duration']); ?> min</td>
                                <td><?php echo e($zone['available_spaces']); ?>/<?php echo e($zone['capacity']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $zone['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo e(ucfirst($zone['status'])); ?>
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
            </div>
        </div>
    </div>
</div>
