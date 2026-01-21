<?php
// Debug script for Mohaa API
define('SMF', 1);

// Mock settings if needed, or include SSI.php to get real environment
// if (file_exists('SSI.php')) {
//     require_once('SSI.php');
// } else {
//     die("SSI.php not found. Run this from SMF root.");
// }

// MOCK SMF ENVIRONMENT
$modSettings = [
    'mohaa_stats_api_url' => 'http://localhost:8080',
    'mohaa_stats_server_token' => 'debug-token',
    'mohaa_stats_cache_duration' => 60,
    'mohaa_stats_api_timeout' => 5
];

function cache_get_data($key, $ttl) {
    return null; // Force fresh fetch
}

function cache_put_data($key, $data, $ttl) {
    // Noop
}

function clean_cache($prefix) {
    // Noop
}

function obExit($do_header = null, $do_footer = null, $do_stats = false, $do_background = false) {
    exit;
}

// Adjust include path since we are in root and file is in smf-mohaa/Sources/MohaaStats/
require_once('smf-mohaa/Sources/MohaaStats/MohaaStatsAPI.php');

echo "<h1>Mohaa API Debugger</h1>";
echo "<p>API URL: " . $modSettings['mohaa_stats_api_url'] . "</p>";

$client = new MohaaStatsAPIClient();
$client->clearCache(); // Force clear cache

// Test GUID
$guid = "ABCD1234567890EF"; // Test GUID
echo "<h2>Testing GUID: $guid</h2>";

echo "<h3>1. Peak Performance (/peak-performance)</h3>";
$peak = $client->getPlayerPeakPerformance($guid);
if ($peak) {
    echo "<pre>" . print_r($peak, true) . "</pre>";
    if (isset($peak['best_conditions'])) {
        echo "<p style='color:green'>SUCCESS: 'best_conditions' found!</p>";
    } else {
        echo "<p style='color:red'>FAILURE: 'best_conditions' MISSING!</p>";
    }
} else {
    echo "<p style='color:red'>Request Failed (Returned null)</p>";
}

echo "<h3>2. Combos (/combos)</h3>";
$combos = $client->getPlayerComboMetrics($guid);
if ($combos) {
    echo "<pre>" . print_r($combos, true) . "</pre>";
    if (isset($combos['signature'])) {
        echo "<p style='color:green'>SUCCESS: 'signature' found!</p>";
    } else {
        echo "<p style='color:red'>FAILURE: 'signature' MISSING!</p>";
    }
} else {
    echo "<p style='color:red'>Request Failed (Returned null)</p>";
}
