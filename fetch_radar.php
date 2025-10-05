<?php

$dataDir = __DIR__ . "/data";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$now = new DateTime("now", new DateTimeZone("UTC"));
$minutes = (int)$now->format("i");
$roundedMinutes = floor($minutes / 5) * 5;
$now->setTime((int)$now->format("H"), $roundedMinutes, 0);

$timestamps = [];
for ($i = 0; $i < 10; $i++) {
    $time = clone $now;
    $time->sub(new DateInterval("PT" . ($i * 5) . "M"));
    $timestamps[] = $time->format("Ymd_Hi");
}

foreach ($timestamps as $timestamp) {
    $filename = "radar_rzc.{$timestamp}.json";
    $url = "https://www.meteoschweiz.admin.ch/product/output/radar/rzc/{$filename}";
    $output = $dataDir . "/" . $filename;
    $output_compressed = $output . ".gz";
    
    if (file_exists($output) && file_exists($output_compressed)) {
        echo "File already exists: {$output}\n";
        continue;
    }
    
    echo "Fetching: {$url}\n";

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; RadarFetcher/1.0)");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Execute the request
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($data === false || $httpCode !== 200) {
        echo "Error fetching {$filename}: {$error}. HTTP Code: {$httpCode}\n";
        continue;
    }

    $jsonData = json_decode($data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Invalid JSON received for {$filename}\n";
        continue;
    }

    $compressedData = gzencode($data, 9);
    if ($compressedData === false) {
        echo "Error compressing data for {$filename}\n";
        continue;
    }

    file_put_contents($output_compressed, $compressedData);
    file_put_contents($output, $data);
}

?>