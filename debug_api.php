<?php
// Debug settings
ini_set('display_errors', 1);
error_reporting(E_ALL);

$url = "http://localhost:8080/api/v1/stats/player/72750883-29ae-4377-85c4-9367f1f89d1a";

echo "Attempting to fetch: " . $url . "\n\n";

// Method 1: file_get_contents
echo "--- Method 1: file_get_contents ---\n";
if (ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) {
        $e = error_get_last();
        echo "FAILED: " . print_r($e, true) . "\n";
    } else {
        echo "SUCCESS! length: " . strlen($res) . "\n";
        echo "Preview: " . substr($res, 0, 100) . "...\n";
    }
} else {
    echo "SKIPPED: allow_url_fopen is disabled.\n";
}

echo "\n";

// Method 2: cURL (what the specific code uses)
echo "--- Method 2: cURL ---\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_VERBOSE => true, // Output debug info to stderr
    ]);
    
    // Capture verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    curl_close($ch);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);

    echo "HTTP Code: " . $httpCode . "\n";
    if ($errno) {
        echo "CURL Error ($errno): " . $error . "\n";
        echo "Verbose Log:\n" . $verboseLog . "\n";
    } elseif ($httpCode !== 200) {
        echo "API Error Code.\n";
        echo "Response: " . $response . "\n";
    } else {
        echo "SUCCESS!\n";
        echo "Response: " . $response . "\n";
    }

} else {
    echo "SKIPPED: curl extension not installed.\n";
}
