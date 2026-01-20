<?php
/**
 * Install/Update script for MOHAA Team Ecosystem (Phase 7)
 */

// Check for SSI.php
$ssi_layers = array('html');
$file_ssi = dirname(dirname(__FILE__)) . '/SSI.php';

if (file_exists($file_ssi)) {
    require_once($file_ssi);
} else {
    // Fallback: Output Raw SQL for manual execution
    header('Content-Type: text/plain');
    echo "-- SMF Not Found. Please run this SQL manually in your database (e.g., via PHPMyAdmin) --\n\n";
    
    // 1. Create mohaa_team_challenges
    echo "CREATE TABLE IF NOT EXISTS `smf_mohaa_team_challenges` (
      `id_challenge` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `id_team_challenger` int(10) unsigned NOT NULL,
      `id_team_target` int(10) unsigned NOT NULL,
      `challenge_date` int(10) unsigned NOT NULL DEFAULT 0,
      `match_date` int(10) unsigned NOT NULL DEFAULT 0,
      `game_mode` varchar(50) DEFAULT 'tdm',
      `map` varchar(100) DEFAULT '',
      `status` varchar(20) DEFAULT 'pending',
      PRIMARY KEY (`id_challenge`),
      KEY `id_team_challenger` (`id_team_challenger`),
      KEY `id_team_target` (`id_team_target`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;\n\n";

    // 2. Create mohaa_tournament_registrations (Missing from SQL files but used in PHP)
    echo "CREATE TABLE IF NOT EXISTS `smf_mohaa_tournament_registrations` (
      `id_registration` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `id_tournament` int(10) unsigned NOT NULL,
      `id_member` int(10) unsigned NOT NULL,
      `id_team` int(10) unsigned NOT NULL DEFAULT 0,
      `player_guid` varchar(32) NOT NULL DEFAULT '',
      `status` varchar(20) NOT NULL DEFAULT 'pending',
      `registered_date` int(10) unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY (`id_registration`),
      KEY `id_tournament` (`id_tournament`),
      KEY `id_member` (`id_member`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;\n\n";

    // 3. Add columns if missing (SQL does not support 'IF NOT EXISTS' for columns easily, usually requires procedure)
    echo "-- Add columns if they don't exist:\n";
    echo "ALTER TABLE `smf_mohaa_tournaments` ADD COLUMN `id_winner_team` int(10) unsigned NOT NULL DEFAULT 0;\n";
    echo "ALTER TABLE `smf_mohaa_tournament_registrations` ADD COLUMN `id_team` int(10) unsigned NOT NULL DEFAULT 0;\n";
    
    echo "\n-- Done.\n";
    exit;
}

global $smcFunc, $db_prefix;

// 1. Create mohaa_team_challenges table
$tables = [
    'mohaa_team_challenges' => [
        'columns' => [
            ['name' => 'id_challenge', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_team_challenger', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_team_target', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'challenge_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'game_mode', 'type' => 'varchar', 'size' => 50, 'default' => 'tdm'],
            ['name' => 'map', 'type' => 'varchar', 'size' => 100, 'default' => ''],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_challenge']],
            ['type' => 'index', 'columns' => ['id_team_challenger']],
            ['type' => 'index', 'columns' => ['id_team_target']],
        ],
    ],
    // Create registrations table if missing (based on MohaaTournaments.php usage)
    'mohaa_tournament_registrations' => [
        'columns' => [
            ['name' => 'id_registration', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'player_guid', 'type' => 'varchar', 'size' => 32, 'default' => ''],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'],
            ['name' => 'registered_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_registration']],
            ['type' => 'index', 'columns' => ['id_tournament']],
            ['type' => 'index', 'columns' => ['id_member']],
        ],
    ]
];

foreach ($tables as $table => $data) {
    if (empty($smcFunc['db_table_structure']('{db_prefix}' . $table))) {
        $smcFunc['db_create_table']('{db_prefix}' . $table, $data['columns'], $data['indexes']);
        echo "Created table: $table<br>";
    } else {
         echo "Table already exists: $table<br>";
    }
}

// 2. Add id_team to mohaa_tournament_registrations
$columns_regs = [
    [
        'name' => 'id_team',
        'type' => 'int',
        'size' => 10,
        'unsigned' => true,
        'default' => 0,
        'null' => false
    ],
];
$smcFunc['db_add_column']('{db_prefix}mohaa_tournament_registrations', $columns_regs[0], [], 'ignore');
echo "Added id_team to mohaa_tournament_registrations<br>";

// 3. Add id_winner_team to mohaa_tournaments
$columns_tourn = [
    [
        'name' => 'id_winner_team',
        'type' => 'int',
        'size' => 10,
        'unsigned' => true,
        'default' => 0,
        'null' => false
    ],
];
$smcFunc['db_add_column']('{db_prefix}mohaa_tournaments', $columns_tourn[0], [], 'ignore');
echo "Added id_winner_team to mohaa_tournaments<br>";

echo 'Database update ecosystem complete!';
