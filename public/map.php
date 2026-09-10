<?php
/**
 * Public Map Handler
 */

namespace App\Models;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bootstrap.php';

$zoneModel = new \App\Models\ZoneModel();
$zones = $zoneModel->getActive();

// Minimal map page without full navbar
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Map - ' . APP_NAME . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="' . APP_URL . '/assets/css/main.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="' . APP_URL . '/">' . APP_NAME . '</a>
            <a href="' . APP_URL . '/login" class="btn btn-sm btn-light">Login</a>
        </div>
    </nav>
    <div class="container-fluid py-3">
        <h2 class="mb-3">Parking Zones Map</h2>
        <div id="map" style="height: 600px;"></div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const map = L.map("map").setView([5.4141, 100.3288], 12);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "OpenStreetMap"
        }).addTo(map);
        const zones = ' . json_encode($zones) . ';
        zones.forEach(z => {
            if (z.latitude && z.longitude) {
                L.marker([parseFloat(z.latitude), parseFloat(z.longitude)])
                    .addTo(map)
                    .bindPopup("<strong>" + z.name + "</strong><br>Rate: RM " + z.hourly_rate + "/hr<br>Available: " + z.available_spaces + "/" + z.capacity);
            }
        });
    </script>
</body>
</html>';
