<?php
/**
 * Setup Tournament Database Tables
 * Run this once to install the schema.
 */

// Hook into SMF
$ssi_layers = array('html');
$file = dirname(__FILE__) . '/SSI.php';
if (file_exists($file))
    require_once($file);
elseif (file_exists('../SSI.php'))
    require_once('../SSI.php');
elseif (file_exists('../../SSI.php')) 
    require_once('../../SSI.php');
else
    die('Cannot find SSI.php');

global $smcFunc, $db_prefix;

echo "Installing Tournament Tables...\n";

$tables = [];

// 1. Tournaments Table
$tables[] = [
    'name' => 'mohaa_tournaments',
    'columns' => [
        ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
        ['name' => 'name', 'type' => 'varchar', 'size' => 255],
        ['name' => 'description', 'type' => 'text'],
        ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'open'], // open, active, completed, archived
        ['name' => 'format', 'type' => 'varchar', 'size' => 20, 'default' => 'single_elim'], // single_elim, double_elim, round_robin
        ['name' => 'start_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'max_teams', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 16],
        ['name' => 'winner_id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
    ],
    'indexes' => [
        ['type' => 'primary', 'columns' => ['id_tournament']],
        ['type' => 'index', 'columns' => ['status']],
    ]
];

// 2. Tournament Participants Table
$tables[] = [
    'name' => 'mohaa_tournament_participants',
    'columns' => [
        ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true],
        ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
        ['name' => 'seed', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'registration_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'], // approved, pending, disqualified
    ],
    'indexes' => [
        ['type' => 'primary', 'columns' => ['id_tournament', 'id_team']],
        ['type' => 'index', 'columns' => ['id_team']],
    ]
];

// 3. Tournament Matches Table
$tables[] = [
    'name' => 'mohaa_tournament_matches',
    'columns' => [
        ['name' => 'id_match', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
        ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true],
        ['name' => 'round', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1],
        ['name' => 'bracket_group', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0], // 0=Main/Upper, 1=Lower
        ['name' => 'id_team_a', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'id_team_b', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'score_a', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'score_b', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'winner_id', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
    ],
    'indexes' => [
        ['type' => 'primary', 'columns' => ['id_match']],
        ['type' => 'index', 'columns' => ['id_tournament']],
        ['type' => 'index', 'columns' => ['id_team_a']],
        ['type' => 'index', 'columns' => ['id_team_b']],
    ]
];

foreach ($tables as $table) {
    if (empty($smcFunc['db_create_table'])) {
        echo "Error: db_create_table not available. Are you inside SMF context?\n";
        exit;
    }
    
    $smcFunc['db_create_table']('{db_prefix}' . $table['name'], $table['columns'], $table['indexes']);
    echo "Created/Updated table: {$table['name']}\n";
}

echo "Database Install Complete!\n";
?>
