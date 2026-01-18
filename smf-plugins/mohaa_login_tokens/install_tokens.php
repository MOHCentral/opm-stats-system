<?php
/**
 * MOHAA Login Tokens Database Installation
 *
 * @package MohaaLoginTokens
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $db_prefix;

// Login tokens table - used for in-game authentication
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_login_tokens (
        id_token INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_member INT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL,
        token_type ENUM('login', 'api') NOT NULL DEFAULT 'login',
        created_date INT UNSIGNED NOT NULL DEFAULT 0,
        expires_date INT UNSIGNED NOT NULL DEFAULT 0,
        used_date INT UNSIGNED DEFAULT NULL,
        used_ip VARCHAR(45) DEFAULT NULL,
        player_guid VARCHAR(64) DEFAULT NULL,
        status ENUM('active', 'used', 'expired', 'revoked') NOT NULL DEFAULT 'active',
        PRIMARY KEY (id_token),
        UNIQUE KEY idx_token (token),
        KEY idx_member (id_member),
        KEY idx_expires (expires_date),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Login sessions - tracks active game sessions linked to forum accounts
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_login_sessions (
        id_session INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_member INT UNSIGNED NOT NULL,
        player_guid VARCHAR(64) NOT NULL,
        server_ip VARCHAR(45),
        server_port INT UNSIGNED,
        login_time INT UNSIGNED NOT NULL DEFAULT 0,
        last_seen INT UNSIGNED NOT NULL DEFAULT 0,
        logout_time INT UNSIGNED DEFAULT NULL,
        status ENUM('active', 'offline') NOT NULL DEFAULT 'active',
        PRIMARY KEY (id_session),
        KEY idx_member (id_member),
        KEY idx_guid (player_guid),
        KEY idx_active (status, last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Token usage log for security auditing
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_token_log (
        id_log INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_member INT UNSIGNED NOT NULL,
        token_prefix VARCHAR(8) NOT NULL,
        action ENUM('created', 'used', 'expired', 'revoked') NOT NULL,
        ip_address VARCHAR(45),
        player_guid VARCHAR(64),
        log_time INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_log),
        KEY idx_member (id_member),
        KEY idx_time (log_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
