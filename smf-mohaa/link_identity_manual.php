#!/usr/bin/env php
<?php
/**
 * Link elgan's SMF account (ID: 1) to game GUID
 * This script finds the SMF installation and uses its database connection
 */

// Try to find SMF installation
$possiblePaths = [
    '/var/www/html',
    '/var/www',
    __DIR__ . '/../../../smf',  // Assuming SMF might be nearby
    '/home/elgan/smf',
    '/opt/lampp/htdocs',
];

$smfPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path . '/Settings.php')) {
        $smfPath = $path;
        break;
    }
}

if (!$smfPath) {
    echo "ERROR: Could not find SMF installation.\n";
    echo "Please manually run this SQL:\n\n";
    echo file_get_contents(__DIR__ . '/link_identity.sql');
    exit(1);
}

echo "Found SMF at: {$smfPath}\n";

// Load SMF settings
require_once($smfPath . '/Settings.php');

// Connect to database
$db = new mysqli($db_server, $db_user, $db_passwd, $db_name);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "Connected to database\n";

// Create table
$createTable = str_replace('{db_prefix}', $db_prefix ?? 'smf_', file_get_contents(__DIR__ . '/link_identity.sql'));
$queries = explode(';', $createTable);

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || strpos($query, '--') === 0) continue;
    
    if (!$db->query($query)) {
        echo "Warning: " . $db->error . "\n";
    }
}

// Verify
$result = $db->query("SELECT * FROM {$db_prefix}mohaa_identities WHERE id_member = 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "\n✓ Successfully linked:\n";
    print_r($row);
} else {
    echo "\n✗ Failed to verify link\n";
}

$db->close();
