-- Link elgan's SMF account to game GUID
-- Run this with: php smf-mohaa/link_identity_manual.php

USE smf;

-- Create table if it doesn't exist
CREATE TABLE IF NOT EXISTS smf_mohaa_identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    player_guid VARCHAR(36) NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    linked_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_member (id_member),
    UNIQUE KEY unique_guid (player_guid),
    KEY idx_member (id_member)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert or update the link
INSERT INTO smf_mohaa_identities (id_member, player_guid, player_name, verified)
VALUES (1, '72750883-29ae-4377-85c4-9367f1f89d1a', 'elgan', 1)
ON DUPLICATE KEY UPDATE 
    player_guid = '72750883-29ae-4377-85c4-9367f1f89d1a',
    player_name = 'elgan',
    linked_date = NOW();

SELECT * FROM smf_mohaa_identities WHERE id_member = 1;
