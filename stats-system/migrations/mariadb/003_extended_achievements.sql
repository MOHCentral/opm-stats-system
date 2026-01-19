-- Extended Achievements Pack
-- 80+ additional creative achievements
-- Run: docker exec smf-smf-db-1 mysql -uroot -proot_password smf < migrations/mariadb/003_extended_achievements.sql

-- ==================== HOLIDAY / EVENT BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('christmas_warrior', 'Christmas Warrior', 'Get 25 kills on Christmas Day', 'situational', 4, 'christmas', 'christmas_kills', 25, 150),
('new_year_newbie', 'New Year Newbie', 'Play your first match on New Years Day', 'situational', 2, 'newyear', 'newyear_match', 1, 50),
('halloween_horror', 'Halloween Horror', 'Get 31 kills on Halloween', 'situational', 4, 'halloween', 'halloween_kills', 31, 150),
('friday_13th', 'Friday the 13th', 'Get 13 kills on Friday the 13th', 'situational', 5, 'friday13', 'friday13_kills', 13, 200),
('independence_day', 'Independence Day', 'Get a grenade multi-kill on July 4th', 'situational', 4, 'independence', 'july4_grenade_multi', 1, 150),
('veterans_day_salute', 'Veterans Day Salute', 'Play 3 matches on Veterans Day', 'dedication', 3, 'veterans', 'veterans_day_matches', 3, 100),
('april_fools', 'April Fools', 'Kill yourself with your own grenade on April 1st', 'troll', 3, 'aprilfools', 'april_fools_suicide', 1, 75),
('full_moon_madness', 'Full Moon Madness', 'Get 15 kills during a full moon', 'situational', 3, 'fullmoon', 'fullmoon_kills', 15, 100);

-- ==================== PRO / TOURNAMENT BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('tournament_victor', 'Tournament Victor', 'Win a tournament match', 'hardcore', 5, 'tournament', 'tournament_wins', 1, 300),
('tournament_champion', 'Tournament Champion', 'Win a tournament finals', 'hardcore', 7, 'champion', 'tournament_finals_win', 1, 750),
('league_player', 'League Player', 'Participate in 10 ranked matches', 'dedication', 3, 'league', 'ranked_matches', 10, 100),
('ranked_warrior', 'Ranked Warrior', 'Participate in 50 ranked matches', 'dedication', 4, 'ranked', 'ranked_matches', 50, 200),
('scrim_star', 'Scrim Star', 'Play 25 scrimmage matches', 'dedication', 4, 'scrim', 'scrim_matches', 25, 175),
('clan_pride', 'Clan Pride', 'Play 100 matches with your clan', 'dedication', 5, 'clan', 'clan_matches', 100, 250),
('mvp_award', 'MVP', 'Be voted MVP of a match 10 times', 'hardcore', 5, 'mvp', 'mvp_votes', 10, 300),
('team_captain', 'Team Captain', 'Lead your team to 50 victories', 'objective', 5, 'captain', 'team_leader_wins', 50, 275);

-- ==================== ULTRA RARE ACHIEVEMENTS ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('the_impossible', 'The Impossible', 'Win a match 1v6 (alone vs full team)', 'hardcore', 8, 'impossible', 'solo_team_win', 1, 1000),
('zero_to_hero', 'Zero to Hero', 'Win after being down 0-10 in rounds', 'hardcore', 7, 'zerohero', 'massive_comeback', 1, 750),
('perfect_season', 'Perfect Season', 'Win 20 matches in a row', 'hardcore', 8, 'perfect_season', 'win_streak_20', 1, 1000),
('the_untouchable', 'The Untouchable', 'Win 5 matches in a row without dying once', 'hardcore', 8, 'untouchable2', 'deathless_win_streak', 5, 1000),
('ace_in_one', 'Ace in One', 'Kill entire enemy team in under 10 seconds', 'hardcore', 7, 'aceinone', 'speed_team_wipe', 1, 750),
('the_collector', 'The Collector', 'Unlock 500 achievements', 'dedication', 8, 'collector', 'achievement_count', 500, 1000),
('point_millionaire', 'Point Millionaire', 'Earn 1,000,000 total score points', 'dedication', 8, 'millionaire', 'total_score', 1000000, 1000);

-- ==================== COMMUNITY MILESTONES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('pioneer', 'Pioneer', 'One of the first 50 players to register', 'dedication', 6, 'pioneer', 'pioneer', 1, 400),
('alpha_tester', 'Alpha Tester', 'Participated in alpha testing', 'dedication', 6, 'alpha', 'alpha_tester', 1, 400),
('bug_hunter', 'Bug Hunter', 'Reported 5 confirmed bugs', 'dedication', 4, 'bughunter', 'bugs_reported', 5, 200),
('community_hero', 'Community Hero', 'Contributed to the community significantly', 'dedication', 7, 'communityhero', 'community_contribution', 1, 500),
('streamer', 'Streamer', 'Stream the game for 10 hours total', 'dedication', 4, 'streamer', 'stream_time', 36000, 200),
('content_creator', 'Content Creator', 'Create content viewed by 1000+ players', 'dedication', 5, 'content', 'content_views', 1000, 300);

-- ==================== VEHICLE BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('tank_commander', 'Tank Commander', 'Get 50 kills in a tank', 'weapon', 4, 'tank', 'tank_kills', 50, 175),
('tank_ace', 'Tank Ace', 'Get 200 kills in a tank', 'weapon', 5, 'tankace', 'tank_kills', 200, 300),
('jeep_warrior', 'Jeep Warrior', 'Get 25 roadkills with a jeep', 'weapon', 4, 'jeep', 'roadkills', 25, 150),
('tank_destroyer', 'Tank Destroyer', 'Destroy 25 enemy tanks', 'objective', 4, 'tankdestroyer', 'tanks_destroyed', 25, 175),
('air_support', 'Air Support', 'Get 10 kills with mounted MG on vehicle', 'weapon', 3, 'airsupport', 'mounted_mg_kills', 10, 100);

-- ==================== SOCIAL BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('chatty', 'Chatty', 'Send 1000 chat messages', 'dedication', 2, 'chatty', 'chat_messages', 1000, 50),
('silent_killer', 'Silent Killer', 'Get 50 kills without sending a single chat message', 'situational', 4, 'silent', 'silent_kills', 50, 175),
('good_game', 'Good Game', 'Say "gg" at the end of 100 matches', 'dedication', 2, 'gg', 'gg_messages', 100, 50),
('sportsman', 'Sportsman', 'Compliment an enemy player 25 times', 'dedication', 3, 'sportsman', 'compliments', 25, 100),
('trash_talker', 'Trash Talker', 'Get reported for trash talk 5 times (shame!)', 'shame', 2, 'trashtalker', 'trash_reports', 5, 25);

-- ==================== ENVIRONMENTAL BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('night_fighter', 'Night Fighter', 'Get 100 kills on night maps', 'map', 3, 'nightfighter', 'night_map_kills', 100, 100),
('snow_soldier', 'Snow Soldier', 'Get 100 kills on snow maps', 'map', 3, 'snowsoldier', 'snow_map_kills', 100, 100),
('desert_fox', 'Desert Fox', 'Get 100 kills on desert maps', 'map', 3, 'desertfox', 'desert_map_kills', 100, 100),
('urban_warrior', 'Urban Warrior', 'Get 100 kills on urban maps', 'map', 3, 'urbanwarrior', 'urban_map_kills', 100, 100),
('forest_ranger', 'Forest Ranger', 'Get 100 kills on forest maps', 'map', 3, 'forestranger', 'forest_map_kills', 100, 100),
('bridge_controller', 'Bridge Controller', 'Get 50 kills on bridges', 'map', 4, 'bridge', 'bridge_kills', 50, 150),
('rooftop_sniper', 'Rooftop Sniper', 'Get 50 kills from rooftops', 'tactical', 4, 'rooftop', 'rooftop_kills', 50, 150),
('basement_dweller', 'Basement Dweller', 'Spend 30 minutes in basements total', 'map', 2, 'basement', 'basement_time', 1800, 50);

-- ==================== TIMING BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('speed_demon', 'Speed Demon', 'Complete an objective in record time', 'objective', 5, 'speeddemon', 'speed_record', 1, 250),
('slow_and_steady', 'Slow and Steady', 'Win a round lasting over 10 minutes', 'situational', 3, 'slowsteady', 'long_round_win', 1, 100),
('quick_match', 'Quick Match', 'Complete a match in under 5 minutes', 'situational', 3, 'quickmatch', 'fast_match', 1, 100),
('marathon_match', 'Marathon Match', 'Play a match lasting over 1 hour', 'dedication', 4, 'marathonmatch', 'long_match', 1, 150),
('all_nighter', 'All Nighter', 'Play from 11 PM to 5 AM in one session', 'dedication', 4, 'allnighter', 'all_night_session', 1, 175);

-- ==================== PRECISION BADGES (Extended) ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('sniper_patience', 'Sniper Patience', 'Wait 30+ seconds before taking a kill shot', 'tactical', 4, 'patience', 'patient_snipe', 10, 150),
('quick_scope', 'Quick Scope', 'Kill within 0.3 seconds of scoping', 'tactical', 5, 'quickscope', 'quickscope_kills', 25, 250),
('no_scope_sniper', 'No Scope Sniper', 'Get 50 no-scope sniper kills', 'tactical', 5, 'noscope', 'noscope_kills', 50, 275),
('drag_shot', 'Drag Shot', 'Kill while rapidly moving crosshair', 'tactical', 4, 'dragshot', 'dragshot_kills', 25, 150),
('flick_shot', 'Flick Shot', 'Kill with >90 degree flick in 0.2 seconds', 'tactical', 5, 'flickshot', 'flickshot_kills', 10, 250);

-- ==================== STRATEGIC BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('bait_master', 'Bait Master', 'Lure enemies into 25 traps/crossfires', 'tactical', 4, 'baitmaster', 'bait_kills', 25, 150),
('flanker', 'Flanker', 'Kill 100 enemies from the side', 'tactical', 3, 'flanker', 'flank_kills', 100, 100),
('ambush_predator', 'Ambush Predator', 'Get 50 ambush kills (stationary, waiting)', 'tactical', 4, 'ambush', 'ambush_kills', 50, 150),
('distraction', 'The Distraction', 'Draw fire allowing teammate to get 10 kills', 'objective', 4, 'distraction', 'distraction_assists', 10, 150),
('intel_gatherer', 'Intel Gatherer', 'Spot 100 enemies for your team', 'objective', 3, 'intel', 'enemy_spots', 100, 100);

-- ==================== COMEBACK BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('phoenix_rising', 'Phoenix Rising', 'Win after being down by 5+ rounds', 'situational', 5, 'phoenix', 'comeback_5_rounds', 1, 250),
('never_give_up', 'Never Give Up', 'Win match after being down 1v4', 'situational', 6, 'nevergiveup', 'clutch_1v4_match', 1, 400),
('rally_leader', 'Rally Leader', 'Turn team morale around (win 5 rounds after losing 5)', 'situational', 4, 'rally', 'rally_win', 1, 175),
('underdog', 'Underdog', 'Beat a team with 50% higher average rank', 'situational', 5, 'underdog', 'underdog_wins', 5, 250);

-- ==================== WEAPON MASTERY (Extended) ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('all_rounder', 'All Rounder', 'Get 100 kills with 10 different weapons', 'weapon', 5, 'allrounder', 'weapon_variety', 10, 275),
('weapon_collector', 'Weapon Collector', 'Get at least 1 kill with every weapon', 'weapon', 4, 'weaponcollector', 'all_weapons_used', 1, 200),
('one_trick', 'One Trick Pony', 'Get 1000 kills with a single weapon', 'weapon', 5, 'onetrick', 'single_weapon_kills', 1000, 300),
('shotgun_surgeon', 'Shotgun Surgeon', 'Get 100 shotgun headshots', 'weapon', 5, 'shotgunsurgeon', 'shotgun_headshots', 100, 275),
('pistol_master', 'Pistol Master', 'Get 500 pistol kills', 'weapon', 5, 'pistolmaster', 'pistol_kills', 500, 275),
('knife_only', 'Knife Only', 'Win a match using only melee weapons', 'hardcore', 6, 'knifeonly', 'knife_only_match', 1, 400),
('grenade_expert', 'Grenade Expert', 'Get 5 grenade kills in one life', 'weapon', 5, 'grenadeexpert', 'grenade_kills_life', 5, 275),
('cooking_master', 'Cooking Master', 'Get 25 perfectly cooked grenade kills', 'weapon', 4, 'cookingmaster', 'cooked_grenades', 25, 175);

-- ==================== ENDURANCE BADGES ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('iron_will', 'Iron Will', 'Play 10 matches in a row without leaving', 'dedication', 4, 'ironwill', 'consecutive_matches', 10, 175),
('marathon_day', 'Marathon Day', 'Play for 8 hours in a single day', 'dedication', 5, 'marathonday', 'daily_playtime', 28800, 275),
('weekly_warrior', 'Weekly Warrior', 'Play at least 1 match every day for a week', 'dedication', 3, 'weeklywarrior', 'daily_streak', 7, 100),
('monthly_veteran', 'Monthly Veteran', 'Play at least 1 match every day for a month', 'dedication', 5, 'monthlyveteran', 'daily_streak', 30, 300),
('year_round', 'Year Round', 'Play in every month of the year', 'dedication', 4, 'yearround', 'monthly_activity', 12, 200);

-- ==================== ANTI-ACHIEVEMENTS (Shame Badges) ====================
INSERT IGNORE INTO smf_mohaa_achievement_defs (code, name, description, category, tier, icon, requirement_type, requirement_value, points) VALUES
('butterfingers', 'Butterfingers', 'Drop 50 grenades at your feet', 'shame', 2, 'butterfingers', 'dropped_grenades', 50, 25),
('deaf_ears', 'Deaf Ears', 'Die to footsteps you should have heard 25 times', 'shame', 2, 'deafears', 'sound_deaths', 25, 25),
('tunnel_vision', 'Tunnel Vision', 'Die from enemies you never saw 100 times', 'shame', 3, 'tunnelvision', 'blind_deaths', 100, 50),
('spray_master', 'Spray Master', 'Miss 10000 bullets total', 'shame', 2, 'spraymaster', 'missed_shots', 10000, 25),
('reloader_syndrome', 'Reloader Syndrome', 'Reload after firing 1 bullet 100 times', 'shame', 2, 'reloader', 'premature_reloads', 100, 25),
('wrong_target', 'Wrong Target', 'Shoot teammates 500 times', 'shame', 3, 'wrongtarget', 'friendly_hits', 500, 50);

-- Summary: 80 extended achievements added
SELECT COUNT(*) AS total_achievements FROM smf_mohaa_achievement_defs;
