<?php
// Quick script to link elgan's identity
define('SMF', 1);
require_once('/var/www/html/SSI.php');

$smf_member_id = 1;  // elgan
$player_guid = '72750883-29ae-4377-85c4-9367f1f89d1a';
$player_name = 'elgan';

// Check if table exists
$result = $smcFunc['db_query']('', "SHOW TABLES LIKE '{db_prefix}mohaa_identities'", []);
if ($smcFunc['db_num_rows']($result) == 0) {
    echo "Creating mohaa_identities table...\n";
    
    $smcFunc['db_query']('', "
        CREATE TABLE IF NOT EXISTS {db_prefix}mohaa_identities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_member INT NOT NULL,
            player_guid VARCHAR(36) NOT NULL,
            player_name VARCHAR(100) NOT NULL,
            linked_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified TINYINT(1) DEFAULT 1,
            UNIQUE KEY unique_member (id_member),
            UNIQUE KEY unique_guid (player_guid),
            KEY idx_member (id_member)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", []);
    
    echo "✓ Table created\n";
}
$smcFunc['db_free_result']($result);

// Check if already exists
$existing = $smcFunc['db_query']('', '
    SELECT * FROM {db_prefix}mohaa_identities
    WHERE id_member = {int:member} OR player_guid = {string:guid}',
    [
        'member' => $smf_member_id,
        'guid' => $player_guid,
    ]
);

if ($smcFunc['db_num_rows']($existing) > 0) {
    echo "Updating existing link...\n";
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_identities
        SET player_guid = {string:guid}, player_name = {string:name}, linked_date = NOW()
        WHERE id_member = {int:member}',
        [
            'guid' => $player_guid,
            'name' => $player_name,
            'member' => $smf_member_id,
        ]
    );
} else {
    echo "Creating new link...\n";
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_identities',
        [
            'id_member' => 'int',
            'player_guid' => 'string',
            'player_name' => 'string',
            'verified' => 'int',
        ],
        [
            $smf_member_id,
            $player_guid,
            $player_name,
            1,
        ],
        ['id']
    );
}
$smcFunc['db_free_result']($existing);

echo "✓ Successfully linked SMF member {$smf_member_id} to GUID {$player_guid}\n";
