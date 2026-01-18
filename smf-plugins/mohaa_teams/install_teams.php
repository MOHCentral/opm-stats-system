<?php
/**
 * MOHAA Teams Database Installation
 *
 * @package MohaaTeams
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $db_prefix;

// Teams table
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_teams (
        id_team INT UNSIGNED NOT NULL AUTO_INCREMENT,
        team_name VARCHAR(100) NOT NULL,
        team_tag VARCHAR(10),
        description TEXT,
        logo_url VARCHAR(255),
        banner_url VARCHAR(255),
        id_captain INT UNSIGNED NOT NULL,
        founded_date INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('active', 'inactive', 'disbanded') NOT NULL DEFAULT 'active',
        wins INT UNSIGNED NOT NULL DEFAULT 0,
        losses INT UNSIGNED NOT NULL DEFAULT 0,
        draws INT UNSIGNED NOT NULL DEFAULT 0,
        rating INT NOT NULL DEFAULT 1000,
        PRIMARY KEY (id_team),
        UNIQUE KEY idx_name (team_name),
        KEY idx_captain (id_captain),
        KEY idx_rating (rating DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Team members
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_team_members (
        id_team INT UNSIGNED NOT NULL,
        id_member INT UNSIGNED NOT NULL,
        role ENUM('captain', 'officer', 'member', 'substitute') NOT NULL DEFAULT 'member',
        joined_date INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('active', 'inactive', 'kicked', 'left') NOT NULL DEFAULT 'active',
        PRIMARY KEY (id_team, id_member),
        KEY idx_member (id_member)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Team invites
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_team_invites (
        id_invite INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_team INT UNSIGNED NOT NULL,
        id_member INT UNSIGNED NOT NULL,
        id_inviter INT UNSIGNED NOT NULL,
        invite_type ENUM('invite', 'request') NOT NULL DEFAULT 'invite',
        status ENUM('pending', 'accepted', 'declined', 'expired') NOT NULL DEFAULT 'pending',
        created_date INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_invite),
        KEY idx_team (id_team),
        KEY idx_member (id_member)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Team match history
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_team_matches (
        id_match INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_team INT UNSIGNED NOT NULL,
        id_opponent INT UNSIGNED,
        opponent_name VARCHAR(100),
        id_tournament INT UNSIGNED DEFAULT NULL,
        map VARCHAR(50),
        team_score INT NOT NULL DEFAULT 0,
        opponent_score INT NOT NULL DEFAULT 0,
        result ENUM('win', 'loss', 'draw') NOT NULL,
        match_date INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_match),
        KEY idx_team (id_team),
        KEY idx_date (match_date DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
