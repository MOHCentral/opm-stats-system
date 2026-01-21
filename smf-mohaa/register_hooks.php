#!/usr/bin/env php
<?php
/**
 * Quick hook registration script
 * Run from SMF root: php smf-mohaa/register_hooks.php
 */

// Find SMF root
$smf_root = dirname(__DIR__);
if (!file_exists($smf_root . '/SSI.php')) {
    die("Error: Cannot find SSI.php. Please run this from SMF root directory.\n");
}

// Load SMF
define('SMF', 1);
require_once($smf_root . '/SSI.php');

echo "Registering MOHAA hooks...\n\n";

// Register new actions
$new_hooks = [
    ['integrate_actions', 'MohaaPredictions.php|MohaaPredictions_Actions'],
    ['integrate_actions', 'MohaaComparison.php|MohaaComparison_Actions'],
];

foreach ($new_hooks as list($hook, $function)) {
    add_integration_function($hook, $function, true);
    echo "✓ Registered: $hook -> $function\n";
}

echo "\nDone! All hooks registered.\n";
?>
