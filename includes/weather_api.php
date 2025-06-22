<?php
header('Content-Type: application/json');
$commune = isset($_GET['commune']) ? $_GET['commune'] : 'Paris';

// 1. Géocodage : récupérer latitude/longitude
$geocode_url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($commune) . "&count=1&language=fr&format=json";
$geocode_json = @file_get_contents($geocode_url);
$geocode = json_decode($geocode_json, true);

if (!empty($geocode['results'][0]['latitude']) && !empty($geocode['results'][0]['longitude'])) {
    $lat = $geocode['results'][0]['latitude'];
    $lon = $geocode['results'][0]['longitude'];

    // 2. Appel météo
    $weather_url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&current_weather=true";
    $weather_json = @file_get_contents($weather_url);
    $weather = json_decode($weather_json, true);

    if (!empty($weather['current_weather']['temperature'])) {
        echo json_encode([
            'temperature' => $weather['current_weather']['temperature'],
            'weathercode' => $weather['current_weather']['weathercode']
        ]);
        exit;
    }
}
echo json_encode(['temperature' => null, 'weathercode' => null]);
