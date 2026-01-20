<?php
/**
 * Complete Install/Repair Script for MOHAA Team Ecosystem
 * Creates all necessary tables if they don't exist.
 */

// Handle SSI.php
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php') && !defined('SMF'))
    require_once(dirname(dirname(__FILE__)) . '/SSI.php');
elseif (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
    require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
    die('<b>Error:</b> Cannot install - please verify you put this in the smf-mohaa/install directory or root.');

global $smcFunc, $db_prefix;

echo "Starting Team Ecosystem Restoration...<br><br>";

$tables = [
    // 1. Teams Table
    'mohaa_teams' => [
        'columns' => [
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'team_name', 'type' => 'varchar', 'size' => 255],
            ['name' => 'team_tag', 'type' => 'varchar', 'size' => 10], // e.g. [DW]
            ['name' => 'description', 'type' => 'text', 'default' => ''],
            ['name' => 'logo_url', 'type' => 'varchar', 'size' => 255, 'default' => ''],
            ['name' => 'website', 'type' => 'varchar', 'size' => 255, 'default' => ''],
            ['name' => 'id_captain', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'founded_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'active'],
            ['name' => 'rating', 'type' => 'int', 'size' => 10, 'default' => 1000],
            ['name' => 'wins', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'losses', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'recruiting', 'type' => 'tinyint', 'size' => 4, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_team']],
            ['type' => 'index', 'columns' => ['status']],
            ['type' => 'index', 'columns' => ['rating']],
            ['type' => 'unique', 'columns' => ['team_name']],
        ],
    ],

    // 2. Team Members
    'mohaa_team_members' => [
        'columns' => [
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'role', 'type' => 'varchar', 'size' => 20, 'default' => 'member'], // captain, officer, member, substitute
            ['name' => 'joined_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'active'], // active, left, kicked
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_team', 'id_member']],
            ['type' => 'index', 'columns' => ['id_member']],
        ],
    ],

    // 3. Team Invites
    'mohaa_team_invites' => [
        'columns' => [
            ['name' => 'id_invite', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_member', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_inviter', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'invite_type', 'type' => 'varchar', 'size' => 20, 'default' => 'invite'], // invite, request
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'], // pending, accepted, declined
            ['name' => 'created_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_invite']],
            ['type' => 'index', 'columns' => ['id_team']],
            ['type' => 'index', 'columns' => ['id_member']],
            ['type' => 'index', 'columns' => ['status']],
        ],
    ],

    // 4. Team Matches (History)
    'mohaa_team_matches' => [
        'columns' => [
            ['name' => 'id_match', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_opponent', 'type' => 'int', 'size' => 10, 'unsigned' => true], // Can be 0 if unknown/deleted team
            ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'result', 'type' => 'varchar', 'size' => 10, 'default' => 'win'], // win, loss, draw
            ['name' => 'map', 'type' => 'varchar', 'size' => 100, 'default' => ''],
            ['name' => 'score_us', 'type' => 'int', 'size' => 10, 'default' => 0],
            ['name' => 'score_them', 'type' => 'int', 'size' => 10, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_match']],
            ['type' => 'index', 'columns' => ['id_team']],
            ['type' => 'index', 'columns' => ['match_date']],
        ],
    ],

    // 5. Challenges (Future Matches)
    'mohaa_team_challenges' => [
        'columns' => [
            ['name' => 'id_challenge', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_team_challenger', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_team_target', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'challenge_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'match_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'game_mode', 'type' => 'varchar', 'size' => 50, 'default' => 'tdm'],
            ['name' => 'map', 'type' => 'varchar', 'size' => 100, 'default' => ''],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'], // pending, accepted, declined, completed
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_challenge']],
            ['type' => 'index', 'columns' => ['id_team_target']],
            ['type' => 'index', 'columns' => ['status']],
        ],
    ],
    
    // 6. Tournaments (Ensuring they exist)
    'mohaa_tournaments' => [
        'columns' => [
            ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'name', 'type' => 'varchar', 'size' => 255],
            ['name' => 'description', 'type' => 'text', 'default' => ''],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'open'],
            ['name' => 'format', 'type' => 'varchar', 'size' => 20, 'default' => 'single_elim'],
            ['name' => 'tournament_start', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'max_teams', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 16],
            ['name' => 'id_winner_team', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_tournament']],
            ['type' => 'index', 'columns' => ['status']],
        ],
    ],
    
    // 7. Tournament Registrations
    'mohaa_tournament_registrations' => [
        'columns' => [
            ['name' => 'id_registration', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'id_team', 'type' => 'int', 'size' => 10, 'unsigned' => true],
            ['name' => 'seed', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'registration_date', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            ['name' => 'status', 'type' => 'varchar', 'size' => 20, 'default' => 'pending'],
            // Legacy columns that might have been created by other scripts, keeping for compatibility if needed
            // But we will primarily use id_team
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_registration']],
             ['type' => 'index', 'columns' => ['id_tournament']],
             ['type' => 'index', 'columns' => ['id_team']],
        ],
    ],
    
    // 8. Tournament Matches
    'mohaa_tournament_matches' => [
        'columns' => [
             ['name' => 'id_match', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
             ['name' => 'id_tournament', 'type' => 'int', 'size' => 10, 'unsigned' => true],
             ['name' => 'round', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1],
             ['name' => 'bracket_group', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
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
        ],
    ]
];

foreach ($tables as $table => $data) {
    echo "Creating table: {$table}... ";
    $smcFunc['db_create_table']('{db_prefix}' . $table, $data['columns'], $data['indexes']);
    echo "Done.<br>";
}

// Special check for 'recruiting' column in case table existed but was old
$columns = [
    [
        'name' => 'recruiting',
        'type' => 'tinyint',
        'size' => 4,
        'default' => 0,
        'unsigned' => true,
    ],
];
$smcFunc['db_add_column']('{db_prefix}mohaa_teams', $columns[0], [], 'ignore');
echo "<br>Verified columns.<br>";

echo "<br><b>Success!</b> Team Ecosystem restored.";
?>
