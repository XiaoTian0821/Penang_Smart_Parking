<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-camera text-primary"></i> Vehicle Scanner</h2>
    </div>
</div>

<div class="row g-4">
    <!-- Camera -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-video"></i> Camera</h5>
            </div>
            <div class="card-body">
                <video id="camera" class="w-100 rounded" autoplay playsinline style="max-height: 400px;"></video>
                <canvas id="canvas" class="d-none"></canvas>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" id="captureBtn" onclick="capturePhoto()">
                        <i class="fas fa-camera"></i> Take Photo
                    </button>
                    <button class="btn btn-secondary" onclick="switchCamera()">
                        <i class="fas fa-sync"></i> Switch
                    </button>
                </div>
            </div>
        </div>

        <!-- Zone Selection -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Detection Zone</h5>
            </div>
            <div class="card-body">
                <select class="form-select" id="zoneSelect">
                    <option value="">-- Select Zone (Optional) --</option>
                    <?php foreach ($zones as $zone): ?>
                    <option value="<?php echo $zone['id']; ?>"><?php echo e($zone['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search"></i> Detection Result</h5>
            </div>
            <div class="card-body">
                <div id="capturePreview" class="text-center mb-3">
                    <img id="capturedImage" class="img-fluid rounded" style="max-height: 200px; display: none;">
                    <p class="text-muted" id="previewPlaceholder">Take a photo to see preview</p>
                </div>

                <div id="resultArea" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label text-muted">Vehicle Detected</label>
                        <h4 id="vehicleStatus" class="text-success"><i class="fas fa-check-circle"></i> Yes</h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Plate Number</label>
                        <h3 id="plateNumber" class="text-primary"></h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">AI Confidence</label>
                        <div class="progress">
                            <div id="confidenceBar" class="progress-bar bg-success" style="width: 0%"></div>
                        </div>
                        <small id="confidenceText">0%</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Parking Status</label>
                        <h4 id="parkingStatus"></h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Zone</label>
                        <p id="zoneInfo"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Detection Time</label>
                        <p id="detectionTime"></p>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" id="reviewBtn" style="display: none;" onclick="reviewResult()">
                            <i class="fas fa-clipboard-check"></i> Review & Issue Compound
                        </button>
                        <button class="btn btn-secondary" onclick="resetScan()">
                            <i class="fas fa-redo"></i> New Scan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let stream = null;
let facingMode = 'environment';
let capturedData = null;

async function startCamera() {
    try {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facingMode }
        });
        document.getElementById('camera').srcObject = stream;
    } catch (err) {
        console.error('Camera error:', err);
        alert('Camera access denied. Please allow camera access.');
    }
}

function switchCamera() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    startCamera();
}

function capturePhoto() {
    const video = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    document.getElementById('capturedImage').src = imageData;
    document.getElementById('capturedImage').style.display = 'block';
    document.getElementById('previewPlaceholder').style.display = 'none';

    capturedData = {
        base64: imageData.split(',')[1],
        mime: 'image/jpeg'
    };

    processDetection(imageData);
}

async function processDetection(imageData) {
    const resultArea = document.getElementById('resultArea');
    const zoneId = document.getElementById('zoneSelect').value;

    document.getElementById('plateNumber').textContent = 'Processing...';
    resultArea.style.display = 'block';

    try {
        const response = await fetch('<?php echo APP_URL; ?>/api/ai/plate-recognition.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                image: capturedData.base64,
                mime: capturedData.mime,
                zone_id: zoneId || null
            })
        });

        const result = await response.json();
        displayResult(result);
    } catch (err) {
        document.getElementById('plateNumber').textContent = 'Error';
        document.getElementById('parkingStatus').innerHTML = `<span class="text-danger">Detection Failed: ${err.message}</span>`;
    }
}

function displayResult(result) {
    if (!result.success) {
        document.getElementById('plateNumber').textContent = 'Error';
        document.getElementById('parkingStatus').innerHTML = `<span class="text-danger">${result.message || result.error || 'Detection failed'}</span>`;
        return;
    }

    document.getElementById('vehicleStatus').innerHTML = result.vehicle_detected
        ? '<i class="fas fa-check-circle text-success"></i> Yes'
        : '<i class="fas fa-times-circle text-danger"></i> No';

    if (result.plate) {
        document.getElementById('plateNumber').textContent = result.plate;
    } else {
        document.getElementById('plateNumber').textContent = result.needs_review ? 'Needs Manual Review' : 'Not Detected';
    }

    const confidence = Math.round((result.confidence || 0) * 100);
    document.getElementById('confidenceBar').style.width = confidence + '%';
    document.getElementById('confidenceBar').className = 'progress-bar ' + (confidence >= 75 ? 'bg-success' : confidence >= 50 ? 'bg-warning' : 'bg-danger');
    document.getElementById('confidenceText').textContent = confidence + '%';

    const statusEl = document.getElementById('parkingStatus');
    if (result.violation) {
        statusEl.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${result.violation.replace(/_/g, ' ')}</span>`;
        document.getElementById('reviewBtn').style.display = 'block';
    } else if (result.parking_status === 'active') {
        statusEl.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> PAID</span>';
    } else if (result.parking_status === 'expired') {
        statusEl.innerHTML = '<span class="text-warning"><i class="fas fa-clock"></i> EXPIRED</span>';
    } else if (result.parking_status === 'wrong_zone') {
        statusEl.innerHTML = '<span class="text-warning"><i class="fas fa-map-marker-alt"></i> WRONG ZONE</span>';
    } else {
        statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> NOT PAID</span>';
    }

    document.getElementById('detectionTime').textContent = new Date().toLocaleString();
}

function resetScan() {
    document.getElementById('resultArea').style.display = 'none';
    document.getElementById('capturedImage').style.display = 'none';
    document.getElementById('previewPlaceholder').style.display = 'block';
    document.getElementById('reviewBtn').style.display = 'none';
    capturedData = null;
}

function reviewResult() {
    // Navigate to compound review
    window.location.href = '<?php echo APP_URL; ?>/officer/compounds';
}

// Initialize
startCamera();
</script>
