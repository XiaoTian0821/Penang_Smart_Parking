/* Penang Smart Parking - Main JavaScript */

/**
 * Show flash message as toast
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.role = 'alert';
    toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.querySelector('main .container').insertBefore(toast, document.querySelector('main .container').firstChild);
    setTimeout(() => toast.remove(), 5000);
}

/**
 * Format time ago
 */
function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    return date.toLocaleDateString();
}

/**
 * Make API request with CSRF token
 */
async function apiRequest(url, options = {}) {
    const appUrl = document.querySelector('meta[name="app-url"]')?.content || '';
    const requestUrl = url.startsWith('/') ? appUrl + url : url;
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    };

    const response = await fetch(requestUrl, { ...options, ...defaultOptions });
    const responseText = await response.text();

    try {
        return JSON.parse(responseText);
    } catch (error) {
        return {
            success: false,
            message: response.ok ? 'The server returned an invalid response.' : `Request failed (${response.status}).`
        };
    }
}

/**
 * Add a vehicle for the current customer
 */
async function submitVehicle() {
    const form = document.getElementById('addVehicleForm');
    if (!form || !validateForm('addVehicleForm')) return;

    const formData = new FormData(form);
    const result = await apiRequest('/api/vehicles', {
        method: 'POST',
        body: JSON.stringify({
            action: 'create',
            csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '',
            plate: formData.get('plate'),
            vehicle_type: formData.get('vehicle_type'),
            color: formData.get('color'),
            make: formData.get('make'),
            model: formData.get('model'),
            year: formData.get('year'),
        })
    });

    if (result.success) {
        location.reload();
    } else {
        showToast(result.message || 'Failed to add vehicle', 'danger');
    }
}

/**
 * Delete a vehicle owned by the current customer
 */
async function deleteVehicle(vehicleId) {
    if (!confirm('Are you sure you want to delete this vehicle?')) return;

    const result = await apiRequest('/api/vehicles', {
        method: 'POST',
        body: JSON.stringify({
            action: 'delete',
            vehicle_id: vehicleId,
            csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '',
        })
    });

    if (result.success) {
        location.reload();
    } else {
        showToast(result.message || 'Failed to delete vehicle', 'danger');
    }
}

/**
 * Start parking session
 */
async function startParking(vehicleId, zoneId, gpsLat = null, gpsLng = null) {
    const result = await apiRequest('/api/parking/start.php', {
        method: 'POST',
        body: JSON.stringify({ vehicle_id: vehicleId, zone_id: zoneId, gps_lat: gpsLat, gps_lng: gpsLng })
    });

    if (result.success) {
        showToast(`Parking started! Session: ${result.session_number}`, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.message || result.error || 'Failed to start parking', 'danger');
    }
}

/**
 * End parking session
 */
async function endParking(sessionId) {
    if (!confirm('Are you sure you want to end this parking session?')) return;

    const result = await apiRequest('/api/parking/end.php', {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId })
    });

    if (result.success) {
        showToast('Parking ended successfully', 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.message || result.error || 'Failed to end parking', 'danger');
    }
}

/**
 * Extend parking session
 */
async function extendParking(sessionId, minutes = 60) {
    const result = await apiRequest('/api/parking/extend.php', {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId, minutes: minutes })
    });

    if (result.success) {
        showToast(`Parking extended by ${minutes} minutes`, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.message || result.error || 'Failed to extend parking', 'danger');
    }
}

/**
 * Reload wallet via PayPal
 */
async function reloadWalletPayPal(amount) {
    const result = await apiRequest('/api/paypal/create-order.php', {
        method: 'POST',
        body: JSON.stringify({ amount: amount })
    });

    if (result.success && result.approve_url) {
        window.location.href = result.approve_url;
    } else {
        showToast(result.message || 'Failed to create payment', 'danger');
    }
}

/**
 * Quick wallet reload (admin credit for demo)
 */
async function quickReload(amount) {
    const result = await apiRequest('/api/wallet/reload.php', {
        method: 'POST',
        body: JSON.stringify({ amount: amount })
    });

    if (result.success) {
        showToast(`Wallet reloaded with RM ${amount}`, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message || 'Failed to reload wallet', 'danger');
    }
}

/**
 * Pay a compound
 */
async function payCompound(compoundId, amount) {
    if (!confirm(`Pay RM ${amount} for this compound?`)) return;

    const result = await apiRequest('/api/compound/pay.php', {
        method: 'POST',
        body: JSON.stringify({ compound_id: compoundId })
    });

    if (result.success) {
        showToast('Compound paid successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message || 'Payment failed', 'danger');
    }
}

/**
 * Submit an appeal
 */
async function submitAppeal(compoundId, reason) {
    const result = await apiRequest('/api/compound/appeal.php', {
        method: 'POST',
        body: JSON.stringify({ compound_id: compoundId, reason: reason })
    });

    if (result.success) {
        showToast('Appeal submitted successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message || 'Failed to submit appeal', 'danger');
    }
}

/**
 * Get browser GPS location
 */
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById('gps_lat').value = pos.coords.latitude;
                document.getElementById('gps_lng').value = pos.coords.longitude;
                showToast('Location captured', 'success');
            },
            (err) => {
                showToast('Location access denied', 'warning');
            }
        );
    } else {
        showToast('Geolocation not supported', 'warning');
    }
}

/**
 * Initialize parking map with Leaflet
 */
function initMap(zones, centerLat = 5.4141, centerLng = 100.3288) {
    if (typeof L === 'undefined') return;

    const map = L.map('map').setView([centerLat, centerLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    zones.forEach(zone => {
        if (zone.latitude && zone.longitude) {
            const marker = L.marker([zone.latitude, zone.longitude])
                .addTo(map)
                .bindPopup(`
                    <strong>${zone.name}</strong><br>
                    Code: ${zone.code}<br>
                    Rate: RM ${zone.hourly_rate}/hr<br>
                    Available: ${zone.available_spaces}/${zone.capacity}
                `);
        }
    });

    return map;
}

/**
 * Validate form before submit
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const required = form.querySelectorAll('[required]');
    let valid = true;

    required.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return valid;
}

document.addEventListener('DOMContentLoaded', () => {
    // Handle start parking form
    const startParkingForm = document.getElementById('startParkingForm');
    if (startParkingForm) {
        startParkingForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const vehicleId = startParkingForm.querySelector('select[name="vehicle_id"]').value;
            const zoneId = startParkingForm.querySelector('select[name="zone_id"]').value;
            const gpsLat = startParkingForm.querySelector('input[name="gps_lat"]').value;
            const gpsLng = startParkingForm.querySelector('input[name="gps_lng"]').value;
            
            if (!vehicleId || !zoneId) {
                showToast('Please select both vehicle and zone', 'warning');
                return;
            }
            
            startParking(vehicleId, zoneId, gpsLat || null, gpsLng || null);
        });
    }

    // Handle vehicle management
    document.querySelectorAll('[data-delete-vehicle]').forEach(button => {
        button.addEventListener('click', () => deleteVehicle(Number(button.dataset.deleteVehicle)));
    });

    document.getElementById('submitVehicleButton')?.addEventListener('click', submitVehicle);
});

// Expose to global scope
window.showToast = showToast;
window.timeAgo = timeAgo;
window.startParking = startParking;
window.endParking = endParking;
window.extendParking = extendParking;
window.reloadWalletPayPal = reloadWalletPayPal;
window.quickReload = quickReload;
window.payCompound = payCompound;
window.submitAppeal = submitAppeal;
window.getLocation = getLocation;
window.initMap = initMap;
window.validateForm = validateForm;
window.submitVehicle = submitVehicle;
window.deleteVehicle = deleteVehicle;
