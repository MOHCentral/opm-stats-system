-- Achievement Definitions Seeder
-- Populate smf_mohaa_achievement_defs with new Contextual Badges

-- Ensure the table exists first (basic schema)
CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_achievement_defs` (
  `id_achievement` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(50) NOT NULL DEFAULT 'basic',
  `tier` int(10) unsigned NOT NULL DEFAULT 1,
  `icon` varchar(50) NOT NULL DEFAULT 'trophy',
  `requirement_type` varchar(50) NOT NULL,
  `requirement_value` int(10) unsigned NOT NULL DEFAULT 1,
  `points` int(10) unsigned NOT NULL DEFAULT 10,
  `is_hidden` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_achievement`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert Contextual Badges
-- Using INSERT IGNORE or ON DUPLICATE KEY UPDATE to avoid errors

INSERT INTO `{$db_prefix}mohaa_achievement_defs` (`code`, `name`, `description`, `category`, `tier`, `icon`, `requirement_type`, `requirement_value`, `points`, `is_hidden`, `sort_order`) VALUES
('surgical', 'The Surgeon', 'Achieve 100 Headshots in a single tournament event.', 'tactical', 4, 'surgical', 'headshots_tournament', 100, 500, 0, 10),
('unstoppable', 'Unstoppable Force', 'Win 10 matches in a row without a single loss.', 'dedication', 5, 'unstoppable', 'win_streak', 10, 1000, 0, 20),
('survivalist', 'Survivalist', 'Complete a full match with < 10% HP remaining and 0 deaths.', 'hardcore', 3, 'survivalist', 'hp_survival', 1, 250, 0, 30),
('guardian', 'The Guardian', 'Defend the objective for a total of 5 minutes in one game.', 'objective', 2, 'guardian', 'defense_time', 300, 100, 0, 40),
('resourceful', 'Scavenger', 'Pick up 50 enemy weapons in your career.', 'situational', 1, 'resourceful', 'pickup_weapons', 50, 50, 0, 50),
('ghost', 'Ghost', 'Win a round without being seen or taking damage.', 'tactical', 4, 'ghost', 'stealth_round', 1, 500, 0, 60),
('pacifist', 'Pacifist', 'Win a match with 0 kills and 0 deaths (Objective focus).', 'troll', 3, 'pacifist', 'pacifist_win', 1, 300, 0, 70),
('trigger_happy', 'Spray & Pray', 'Fire 10,000 rounds of ammunition.', 'weapon', 1, 'trigger_happy', 'shots_fired', 10000, 25, 0, 80);
