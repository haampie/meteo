<?php

$dataDir = __DIR__ . "/site/data";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$now = new DateTime("now", new DateTimeZone("UTC"));
$minutes = (int)$now->format("i");
$roundedMinutes = floor($minutes / 5) * 5;
$now->setTime((int)$now->format("H"), $roundedMinutes, 0);

$timestamps = [];
for ($i = 0; $i < 24; $i++) {
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
    
    $data = @file_get_contents($url);
    if ($data === false) {
        echo "Error fetching {$filename}\n";
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