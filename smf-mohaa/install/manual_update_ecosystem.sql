-- SMF Not Found. Please run this SQL manually in your database (e.g., via PHPMyAdmin) --

CREATE TABLE IF NOT EXISTS `smf_mohaa_team_challenges` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `smf_mohaa_tournament_registrations` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Add columns if they don't exist:
ALTER TABLE `smf_mohaa_tournaments` ADD COLUMN `id_winner_team` int(10) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `smf_mohaa_tournament_registrations` ADD COLUMN `id_team` int(10) unsigned NOT NULL DEFAULT 0;

-- Done.
