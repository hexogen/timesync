<?php

use GuzzleHttp\Client;
use Hexogen\Timesync\IPGeolocationClient;
use Hexogen\Timesync\IpifyIPDetector;

include __DIR__ . '/../vendor/autoload.php';

// Replace with your actual API key from ipgeolocation.io
$apiKey = 'YOUR_API_KEY_HERE';

$guzzle = new Client();
$ipDetector = new IpifyIPDetector($guzzle);
$ipGeolocator = new IPGeolocationClient($apiKey, $guzzle, $ipDetector);

try {
    $clock = $ipGeolocator->getCurrentTime();
    // UTC + 2 hours
    echo $clock->now()->format('Y-m-d H:i:s');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}