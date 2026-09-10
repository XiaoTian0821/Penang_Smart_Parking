<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-car text-primary"></i> My Vehicles</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vehicle List</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="fas fa-plus"></i> Add Vehicle
                </button>
            </div>
            <div class="card-body">
                <?php if (count($vehicles) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Type</th>
                                <th>Color</th>
                                <th>Make</th>
                                <th>Model</th>
                                <th>Year</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><strong><?php echo e($vehicle['plate']); ?></strong></td>
                                <td><?php echo e(ucfirst($vehicle['vehicle_type'])); ?></td>
                                <td><?php echo e($vehicle['color'] ?? '-'); ?></td>
                                <td><?php echo e($vehicle['make'] ?? '-'); ?></td>
                                <td><?php echo e($vehicle['model'] ?? '-'); ?></td>
                                <td><?php echo e($vehicle['year'] ?? '-'); ?></td>
                                <td><?php echo e(date('M d, Y', strtotime($vehicle['created_at']))); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" data-delete-vehicle="<?php echo (int)$vehicle['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-car text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No vehicles registered yet.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        <i class="fas fa-plus"></i> Add Your First Vehicle
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addVehicleForm">
                    <div class="mb-3">
                        <label class="form-label">Plate Number *</label>
                        <input type="text" class="form-control" name="plate" required placeholder="e.g., WXY 1234">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Type</label>
                        <select class="form-select" name="vehicle_type">
                            <option value="car">Car</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="van">Van</option>
                            <option value="lorry">Lorry</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="text" class="form-control" name="color">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Make</label>
                        <input type="text" class="form-control" name="make">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="model">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="year" min="1900" max="2030">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitVehicleButton">Save Vehicle</button>
            </div>
        </div>
    </div>
</div>
