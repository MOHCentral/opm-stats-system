-- Tournament Engine Tables
-- Run this in your MySQL database

CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournaments` (
  `id_tournament` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `format` varchar(20) NOT NULL DEFAULT 'single_elim',
  `start_date` int(10) unsigned NOT NULL DEFAULT 0,
  `max_teams` int(10) unsigned NOT NULL DEFAULT 16,
  `winner_id_team` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_tournament`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournament_participants` (
  `id_tournament` int(10) unsigned NOT NULL,
  `id_team` int(10) unsigned NOT NULL,
  `seed` int(10) unsigned NOT NULL DEFAULT 0,
  `registration_date` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id_tournament`,`id_team`),
  KEY `id_team` (`id_team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournament_matches` (
  `id_match` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_tournament` int(10) unsigned NOT NULL,
  `round` int(10) unsigned NOT NULL DEFAULT 1,
  `bracket_group` int(10) unsigned NOT NULL DEFAULT 0,
  `id_team_a` int(10) unsigned NOT NULL DEFAULT 0,
  `id_team_b` int(10) unsigned NOT NULL DEFAULT 0,
  `score_a` int(10) unsigned NOT NULL DEFAULT 0,
  `score_b` int(10) unsigned NOT NULL DEFAULT 0,
  `winner_id` int(10) unsigned NOT NULL DEFAULT 0,
  `match_date` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_match`),
  KEY `id_tournament` (`id_tournament`),
  KEY `id_team_a` (`id_team_a`),
  KEY `id_team_b` (`id_team_b`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
