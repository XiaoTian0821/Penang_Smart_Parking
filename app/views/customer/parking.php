<section class="parking-page">
    <div class="parking-heading">
        <div>
            <span class="parking-kicker"><i class="fas fa-location-dot"></i> Penang parking network</span>
            <h1>Park without the guesswork.</h1>
            <p>Choose your vehicle, find an available zone, and start your session in a few seconds.</p>
        </div>
        <a class="parking-history-link" href="<?php echo APP_URL; ?>/customer/history"><i class="fas fa-clock-rotate-left"></i> View history</a>
    </div>

    <?php if ($activeSession): ?>
    <section class="active-session-panel">
        <div class="active-session-topline">
            <div>
                <span class="status-pulse"></span>
                <span class="eyebrow">Parking in progress</span>
                <h2><?php echo e($activeSession['normalized_plate']); ?> <span>in Zone <?php echo e($activeSession['zone_id']); ?></span></h2>
            </div>
            <span class="active-session-badge">Active now</span>
        </div>
        <div class="active-session-details">
            <div><span>Started</span><strong><?php echo e(date('M d, H:i', strtotime($activeSession['start_time']))); ?></strong></div>
            <div><span>Expires</span><strong><?php echo e(date('M d, H:i', strtotime($activeSession['end_time']))); ?></strong></div>
            <div><span>Duration</span><strong><?php echo e($activeSession['duration_minutes']); ?> min</strong></div>
            <div><span>Prepaid</span><strong>RM <?php echo number_format($activeSession['fee'], 2); ?></strong></div>
            <button class="btn btn-light active-session-action" onclick="endParking(<?php echo $activeSession['id']; ?>)"><i class="fas fa-stop"></i> End session</button>
        </div>
    </section>
    <?php endif; ?>

    <div class="parking-layout">
        <section class="parking-form-panel">
            <div class="panel-heading">
                <div class="panel-icon"><i class="fas fa-play"></i></div>
                <div><span class="eyebrow">New session</span><h2>Start parking</h2></div>
            </div>
            <?php if (count($vehicles) > 0 && count($zones) > 0): ?>
            <form id="startParkingForm">
                <label class="parking-field"><span>Vehicle</span><select name="vehicle_id" required><option value="">Choose a vehicle</option><?php foreach ($vehicles as $vehicle): ?><option value="<?php echo $vehicle['id']; ?>"><?php echo e($vehicle['plate']); ?> · <?php echo e(ucfirst($vehicle['vehicle_type'])); ?></option><?php endforeach; ?></select></label>
                <label class="parking-field"><span>Parking zone</span><select name="zone_id" id="parkingZoneSelect" required><option value="">Choose a zone</option><?php foreach ($zones as $zone): ?><option value="<?php echo $zone['id']; ?>" data-rate="<?php echo e($zone['hourly_rate']); ?>" data-spaces="<?php echo e($zone['available_spaces']); ?>" data-capacity="<?php echo e($zone['capacity']); ?>"><?php echo e($zone['name']); ?> · RM <?php echo number_format($zone['hourly_rate'], 2); ?>/hr</option><?php endforeach; ?></select></label>
                <div id="zoneInfo" class="zone-selection-hint"><i class="fas fa-circle-info"></i><span>Select a zone to see its availability and rate.</span></div>
                <button type="button" class="location-button" onclick="getLocation()"><i class="fas fa-crosshairs"></i><span>Use my current location</span><small id="locationStatus">Optional</small></button>
                <input type="hidden" name="gps_lat" id="gps_lat"><input type="hidden" name="gps_lng" id="gps_lng">
                <button type="submit" class="start-parking-button"><span>Start parking</span><i class="fas fa-arrow-right"></i></button>
            </form>
            <?php else: ?>
            <div class="empty-parking-state"><i class="fas fa-car-side"></i><h3>Almost ready</h3><p><?php echo count($vehicles) === 0 ? 'Add a vehicle before starting a parking session.' : 'No active parking zones are available right now.'; ?></p><a href="<?php echo APP_URL; ?>/customer/<?php echo count($vehicles) === 0 ? 'vehicles' : 'dashboard'; ?>" class="btn btn-outline-primary"><?php echo count($vehicles) === 0 ? 'Manage vehicles' : 'Back to dashboard'; ?></a></div>
            <?php endif; ?>
        </section>

        <section class="parking-map-panel">
            <div class="map-panel-header"><div><span class="eyebrow">Live availability</span><h2>Find your zone</h2></div><span class="map-legend"><i></i> Available</span></div>
            <div id="map"></div>
            <div class="zone-strip"><?php foreach ($zones as $zone): ?><div class="zone-strip-item"><span class="zone-dot"></span><div><strong><?php echo e($zone['name']); ?></strong><small><?php echo e($zone['available_spaces']); ?>/<?php echo e($zone['capacity']); ?> spaces · RM <?php echo number_format($zone['hourly_rate'], 2); ?>/hr</small></div></div><?php endforeach; ?></div>
        </section>
    </div>
</section>

<script>
    const parkingZones = <?php echo json_encode($zones, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const parkingZoneSelect = document.getElementById('parkingZoneSelect');
    const zoneInfo = document.getElementById('zoneInfo');
    parkingZoneSelect?.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (!this.value) {
            zoneInfo.innerHTML = '<i class="fas fa-circle-info"></i><span>Select a zone to see its availability and rate.</span>';
            return;
        }
        zoneInfo.innerHTML = '<i class="fas fa-circle-check"></i><span><strong>' + option.dataset.spaces + ' spaces available</strong> · RM ' + Number(option.dataset.rate).toFixed(2) + '/hr</span>';
    });
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('map') && typeof initMap === 'function') initMap(parkingZones);
    });
</script>
