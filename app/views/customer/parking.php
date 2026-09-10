<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-parking text-primary"></i> Parking</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Active Session -->
    <?php if ($activeSession): ?>
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Active Parking Session</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Session:</strong> <?php echo e($activeSession['session_number']); ?></p>
                        <p><strong>Plate:</strong> <?php echo e($activeSession['normalized_plate']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Start:</strong> <?php echo e(date('M d, H:i', strtotime($activeSession['start_time']))); ?></p>
                        <p><strong>End:</strong> <?php echo e(date('M d, H:i', strtotime($activeSession['end_time']))); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Zone:</strong> <?php echo e($activeSession['zone_id']); ?></p>
                        <p><strong>Duration:</strong> <?php echo e($activeSession['duration_minutes']); ?> min</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Fee:</strong> RM <?php echo number_format($activeSession['fee'], 2); ?></p>
                        <button class="btn btn-danger w-100" onclick="endParking(<?php echo $activeSession['id']; ?>)">
                            <i class="fas fa-stop"></i> End Parking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Start Parking -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-play"></i> Start Parking</h5>
            </div>
            <div class="card-body">
                <form id="startParkingForm">
                    <div class="mb-3">
                        <label class="form-label">Select Vehicle *</label>
                        <select class="form-select" name="vehicle_id" required>
                            <option value="">-- Choose Vehicle --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>"><?php echo e($vehicle['plate']); ?> (<?php echo e(ucfirst($vehicle['vehicle_type'])); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Zone *</label>
                        <select class="form-select" name="zone_id" required>
                            <option value="">-- Choose Zone --</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>">
                                <?php echo e($zone['name']); ?> - RM <?php echo number_format($zone['hourly_rate'], 2); ?>/hr
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GPS Location (optional)</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="getLocation()">
                            <i class="fas fa-map-marker-alt"></i> Use My Location
                        </button>
                        <input type="hidden" name="gps_lat" id="gps_lat">
                        <input type="hidden" name="gps_lng" id="gps_lng">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-play"></i> Start Parking
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Zone Info -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Zone Information</h5>
            </div>
            <div class="card-body">
                <div id="zoneInfo">
                    <p class="text-muted">Select a zone to view details.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-map"></i> Parking Map</h5>
            </div>
            <div class="card-body">
                <div id="map" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>
