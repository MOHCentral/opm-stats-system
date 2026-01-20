<?php
/**
 * Install/Update script for MOHAA Teams Recruitment feature
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
    require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
    die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc, $db_prefix;

// Define columns to add
$columns = [
    [
        'name' => 'recruiting',
        'type' => 'tinyint',
        'size' => 4,
        'default' => 0,
        'unsigned' => true,
    ],
];

// Add columns if they don't exist
$smcFunc['db_add_column']('{db_prefix}mohaa_teams', $columns[0], [], 'ignore');

echo 'Database update complete! Added "recruiting" column to mohaa_teams table.';
