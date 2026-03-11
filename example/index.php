<?php

use GuzzleHttp\Client;
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;
use Hexogen\Timesync\SyncService;

include __DIR__ . '/../vendor/autoload.php';

// Replace with your actual API key from ipgeolocation.io
$apiKey = 'YOUR_API_KEY_HERE';

$httpClient = new Client();
$ipDetector = new IpifyIPDetector($httpClient);
$syncClient = new IPGeolocationClient($apiKey, $httpClient);
$syncService = new SyncService($syncClient, $ipDetector);

try {
    $clock = $syncService->getCurrentTime();
    echo $clock->now()->format('Y-m-d H:i:s.u T') . "\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
