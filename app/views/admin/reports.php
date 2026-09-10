<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-chart-bar text-primary"></i> Reports</h2>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Parking Sessions by Zone</h5>
            </div>
            <div class="card-body">
                <canvas id="zoneChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Revenue Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Zone Summary</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Zone</th><th>Code</th><th>Rate</th><th>Available</th><th>Capacity</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zones as $zone): ?>
                        <tr>
                            <td><?php echo e($zone['name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo e($zone['code']); ?></span></td>
                            <td>RM <?php echo number_format($zone['hourly_rate'], 2); ?></td>
                            <td><?php echo e($zone['available_spaces']); ?></td>
                            <td><?php echo e($zone['capacity']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Zone Chart
new Chart(document.getElementById('zoneChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($zones, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($zones, 'available_spaces')); ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d']
        }]
    }
});

// Revenue Chart (demo data)
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Revenue (RM)',
            data: [150, 200, 180, 220, 300, 250, 180],
            backgroundColor: '#0d6efd'
        }]
    }
});
</script>
