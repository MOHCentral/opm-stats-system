<?php
/**
 * MOHAA Tournaments Database Installation
 *
 * @package MohaaTournaments
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $db_prefix;

// Tournaments table
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_tournaments (
        id_tournament INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_creator INT UNSIGNED NOT NULL DEFAULT 0,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        tournament_type ENUM('single_elim', 'double_elim', 'round_robin', 'swiss') NOT NULL DEFAULT 'single_elim',
        team_size TINYINT UNSIGNED NOT NULL DEFAULT 1,
        max_teams INT UNSIGNED NOT NULL DEFAULT 16,
        game_mode VARCHAR(50) NOT NULL DEFAULT 'tdm',
        maps TEXT,
        rules TEXT,
        prize_info TEXT,
        status ENUM('draft', 'registration', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
        registration_start INT UNSIGNED NOT NULL DEFAULT 0,
        registration_end INT UNSIGNED NOT NULL DEFAULT 0,
        tournament_start INT UNSIGNED NOT NULL DEFAULT 0,
        tournament_end INT UNSIGNED NOT NULL DEFAULT 0,
        created_date INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_tournament),
        KEY idx_status (status),
        KEY idx_creator (id_creator)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Tournament registrations
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_tournament_registrations (
        id_registration INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_tournament INT UNSIGNED NOT NULL,
        id_team INT UNSIGNED DEFAULT NULL,
        id_member INT UNSIGNED NOT NULL,
        player_guid VARCHAR(64),
        status ENUM('pending', 'approved', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
        registered_date INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_registration),
        UNIQUE KEY idx_unique_reg (id_tournament, id_member),
        KEY idx_tournament (id_tournament),
        KEY idx_team (id_team)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Tournament matches (bracket)
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_tournament_matches (
        id_match INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_tournament INT UNSIGNED NOT NULL,
        round_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
        match_number INT UNSIGNED NOT NULL DEFAULT 1,
        bracket_position VARCHAR(20),
        id_team1 INT UNSIGNED DEFAULT NULL,
        id_team2 INT UNSIGNED DEFAULT NULL,
        id_player1 INT UNSIGNED DEFAULT NULL,
        id_player2 INT UNSIGNED DEFAULT NULL,
        team1_score INT NOT NULL DEFAULT 0,
        team2_score INT NOT NULL DEFAULT 0,
        id_winner INT UNSIGNED DEFAULT NULL,
        game_match_id VARCHAR(64),
        status ENUM('pending', 'scheduled', 'live', 'completed', 'forfeit') NOT NULL DEFAULT 'pending',
        scheduled_time INT UNSIGNED DEFAULT NULL,
        completed_time INT UNSIGNED DEFAULT NULL,
        PRIMARY KEY (id_match),
        KEY idx_tournament (id_tournament),
        KEY idx_round (id_tournament, round_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Tournament admins
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_tournament_admins (
        id_tournament INT UNSIGNED NOT NULL,
        id_member INT UNSIGNED NOT NULL,
        role ENUM('owner', 'admin', 'referee') NOT NULL DEFAULT 'admin',
        PRIMARY KEY (id_tournament, id_member)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
