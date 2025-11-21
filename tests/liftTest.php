<?php

$url = "http://127.0.0.1:8000/api/lifts";

// ----------------------------------------------
// Generate multiple request bodies automatically
// Max floor = 16
// ----------------------------------------------
$requestBodies = [];

for ($i = 0; $i < 20; $i++) {
    $requestBodies[] = [
        "current_floor" => rand(0, 16),
        "direction"     => rand(0, 1) === 1 ? "up" : "down"
    ];
}

// Optionally add manually crafted test payloads
$requestBodies[] = ["current_floor" => 0, "direction" => "up"];
$requestBodies[] = ["current_floor" => 16, "direction" => "down"];
$requestBodies[] = ["current_floor" => 8, "direction" => "up"];
$requestBodies[] = ["current_floor" => 15, "direction" => "down"];

// ----------------------------------------------
// Configure parallel curl handlers
// ----------------------------------------------
$multiHandle = curl_multi_init();
$curlHandles = [];

foreach ($requestBodies as $index => $body) {

    $jsonData = json_encode($body);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($jsonData)
        ],
        CURLOPT_POSTFIELDS     => $jsonData,
    ]);

    $curlHandles[$index] = $ch;
    curl_multi_add_handle($multiHandle, $ch);
}

// ----------------------------------------------
// Execute all requests in parallel
// ----------------------------------------------
$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// ----------------------------------------------
// Read the results
// ----------------------------------------------
foreach ($curlHandles as $index => $ch) {
    echo "==== Request " . ($index + 1) . " ====\n";
    echo "Request Body:\n";
    print_r($requestBodies[$index]);

    $response = curl_multi_getcontent($ch);
    echo "Response:\n$response\n\n";

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

// Close multi handle
curl_multi_close($multiHandle);

?>
