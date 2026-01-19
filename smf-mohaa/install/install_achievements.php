<?php
/**
 * MOHAA Achievements Database Installation
 *
 * @package MohaaAchievements
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

global $smcFunc, $db_prefix;

// Achievement definitions table
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_achievement_defs (
        id_achievement INT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(64) NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        category ENUM('basic', 'weapon', 'tactical', 'humiliation', 'shame', 'map', 'dedication', 'secret', 'hitbox', 'movement', 'objective', 'physics', 'hardcore', 'troll', 'situational') NOT NULL DEFAULT 'basic',
        tier TINYINT UNSIGNED NOT NULL DEFAULT 1,
        icon VARCHAR(100),
        requirement_type VARCHAR(50) NOT NULL,
        requirement_value INT UNSIGNED NOT NULL DEFAULT 1,
        points INT UNSIGNED NOT NULL DEFAULT 10,
        is_hidden TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_achievement),
        UNIQUE KEY idx_code (code),
        KEY idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Player achievements (unlocked)
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_player_achievements (
        id_unlock INT UNSIGNED NOT NULL AUTO_INCREMENT,
        id_member INT UNSIGNED NOT NULL,
        player_guid VARCHAR(64),
        id_achievement INT UNSIGNED NOT NULL,
        unlocked_date INT UNSIGNED NOT NULL DEFAULT 0,
        match_id VARCHAR(64),
        progress INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_unlock),
        UNIQUE KEY idx_member_achievement (id_member, id_achievement),
        KEY idx_member (id_member),
        KEY idx_date (unlocked_date DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Achievement progress tracking
$smcFunc['db_query']('', "
    CREATE TABLE IF NOT EXISTS {$db_prefix}mohaa_achievement_progress (
        id_member INT UNSIGNED NOT NULL,
        id_achievement INT UNSIGNED NOT NULL,
        current_progress INT UNSIGNED NOT NULL DEFAULT 0,
        last_updated INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id_member, id_achievement)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// Insert default achievements
$achievements = [
    // ==================== TIER 1: BASIC TRAINING ====================
    ['first_blood', 'First Blood', 'Get your first kill', 'basic', 1, 'medal_bronze', 'kills', 1, 5],
    ['marksman', 'Marksman', 'Get 100 kills', 'basic', 1, 'medal_bronze', 'kills', 100, 10],
    ['soldier', 'Soldier', 'Get 500 kills', 'basic', 1, 'medal_silver', 'kills', 500, 25],
    ['veteran', 'Veteran', 'Get 1,000 kills', 'basic', 1, 'medal_gold', 'kills', 1000, 50],
    ['war_hero', 'War Hero', 'Get 5,000 kills', 'basic', 1, 'medal_platinum', 'kills', 5000, 100],
    ['legend', 'Legend', 'Get 10,000 kills', 'basic', 1, 'medal_diamond', 'kills', 10000, 250],
    ['immortal', 'Immortal', 'Get 50,000 kills', 'basic', 1, 'trophy_gold', 'kills', 50000, 500],
    ['god_of_war', 'God of War', 'Get 100,000 kills', 'basic', 1, 'trophy_platinum', 'kills', 100000, 1000],
    
    // ==================== TIER 2: WEAPON SPECIALIST ====================
    ['thompson_expert', 'Thompson Expert', 'Get 500 kills with Thompson', 'weapon', 2, 'weapon_thompson', 'weapon_kills_thompson', 500, 50],
    ['kar98_master', 'Kar98k Master', 'Get 500 kills with Kar98k', 'weapon', 2, 'weapon_kar98', 'weapon_kills_kar98k', 500, 50],
    ['garand_guru', 'Garand Guru', 'Get 500 kills with M1 Garand', 'weapon', 2, 'weapon_garand', 'weapon_kills_garand', 500, 50],
    ['mp40_menace', 'MP40 Menace', 'Get 500 kills with MP40', 'weapon', 2, 'weapon_mp40', 'weapon_kills_mp40', 500, 50],
    ['bar_beast', 'BAR Beast', 'Get 500 kills with BAR', 'weapon', 2, 'weapon_bar', 'weapon_kills_bar', 500, 50],
    ['stg44_specialist', 'StG44 Specialist', 'Get 500 kills with StG44', 'weapon', 2, 'weapon_stg44', 'weapon_kills_stg44', 500, 50],
    ['sniper_elite', 'Sniper Elite', 'Get 500 kills with Springfield', 'weapon', 2, 'weapon_springfield', 'weapon_kills_springfield', 500, 50],
    ['grenadier', 'Grenadier', 'Get 100 grenade kills', 'weapon', 2, 'grenade', 'grenade_kills', 100, 50],
    ['pistolero', 'Pistolero', 'Get 100 pistol kills', 'weapon', 2, 'pistol', 'pistol_kills', 100, 50],
    ['knife_fighter', 'Knife Fighter', 'Get 50 melee kills', 'weapon', 2, 'knife', 'melee_kills', 50, 75],
    
    // ==================== TIER 3: TACTICAL & SKILL ====================
    ['headhunter', 'Headhunter', 'Get 100 headshots', 'tactical', 3, 'headshot', 'headshots', 100, 25],
    ['executioner', 'Executioner', 'Get 1,000 headshots', 'tactical', 3, 'headshot_gold', 'headshots', 1000, 100],
    ['surgical_precision', 'Surgical Precision', 'Achieve 50% headshot ratio (min 100 kills)', 'tactical', 3, 'precision', 'headshot_ratio_50', 1, 150],
    ['longshot_king', 'Longshot King', 'Get a kill from over 150 meters', 'tactical', 3, 'longshot', 'kill_distance_150', 1, 75],
    ['wallbanger', 'Wallbanger', 'Get 10 wallbang kills', 'tactical', 3, 'wallbang', 'wallbang_kills', 10, 50],
    ['clutch_master', 'Clutch Master', 'Win a 1v3+ situation', 'tactical', 3, 'clutch', 'clutch_1v3', 1, 100],
    ['unstoppable', 'Unstoppable', 'Get a 10 killstreak', 'tactical', 3, 'streak_10', 'killstreak_10', 1, 75],
    ['rampage', 'Rampage', 'Get a 15 killstreak', 'tactical', 3, 'streak_15', 'killstreak_15', 1, 100],
    ['godlike', 'GODLIKE', 'Get a 25 killstreak', 'tactical', 3, 'streak_25', 'killstreak_25', 1, 200],
    ['double_kill', 'Double Kill', 'Kill 2 enemies within 3 seconds', 'tactical', 3, 'multi_2', 'multikill_2', 1, 25],
    ['triple_kill', 'Triple Kill', 'Kill 3 enemies within 3 seconds', 'tactical', 3, 'multi_3', 'multikill_3', 1, 50],
    ['ultra_kill', 'ULTRA KILL', 'Kill 4 enemies within 3 seconds', 'tactical', 3, 'multi_4', 'multikill_4', 1, 100],
    ['monster_kill', 'M-M-MONSTER KILL', 'Kill 5+ enemies within 3 seconds', 'tactical', 3, 'multi_5', 'multikill_5', 1, 200],
    
    // ==================== TIER 4: HUMILIATION ====================
    ['grave_dancer', 'Grave Dancer', 'Teabag 10 victims', 'humiliation', 4, 'teabag', 'teabags', 10, 25],
    ['nutcracker', 'Nutcracker', 'Get 10 groin shots', 'humiliation', 4, 'nutshot', 'groin_hits', 10, 50],
    ['backbreaker', 'Backbreaker', 'Get 50 backstab kills', 'humiliation', 4, 'backstab', 'backstab_kills', 50, 50],
    ['air_mail', 'Air Mail', 'Kill an enemy while both of you are mid-air', 'humiliation', 4, 'airshot', 'midair_kill', 1, 100],
    ['denied', 'DENIED!', 'Kill an enemy who was reloading', 'humiliation', 4, 'denied', 'reload_kills', 25, 50],
    ['spawn_camper', 'Spawn Camper', 'Get 25 spawn kills (shame on you)', 'humiliation', 4, 'camper', 'spawn_kills', 25, 25],
    ['prone_warrior', 'Prone Warrior', 'Get 100 kills while prone', 'humiliation', 4, 'prone', 'prone_kills', 100, 50],
    ['the_acrobat', 'The Acrobat', 'Get 25 kills while jumping', 'humiliation', 4, 'jump', 'jump_kills', 25, 75],
    ['blind_fire', 'Blind Fire', 'Kill an enemy while flashbanged', 'humiliation', 4, 'blind', 'flash_kills', 5, 75],
    ['last_breath', 'Last Breath', 'Get 10 kills with less than 5 HP', 'humiliation', 4, 'lowHP', 'low_hp_kills', 10, 100],
    
    // ==================== TIER 5: HALL OF SHAME ====================
    ['cannon_fodder', 'Cannon Fodder', 'Die 1,000 times', 'shame', 5, 'shame_deaths', 'deaths', 1000, 10],
    ['gravity_victim', 'Gravity Victim', 'Die from falling 25 times', 'shame', 5, 'shame_fall', 'fall_deaths', 25, 10],
    ['drowned_rat', 'Drowned Rat', 'Drown 10 times', 'shame', 5, 'shame_drown', 'drown_deaths', 10, 10],
    ['team_killer', 'Team Killer', 'Kill 50 teammates (intentional or not)', 'shame', 5, 'shame_tk', 'team_kills', 50, -50],
    ['rage_quitter', 'Rage Quitter', 'Disconnect 25 times mid-match', 'shame', 5, 'shame_quit', 'rage_quits', 25, -25],
    ['dominated', 'Dominated', 'Die to the same player 10 times in one match', 'shame', 5, 'shame_dominated', 'dominated_by_same', 1, 10],
    ['reload_death', 'Empty Clip', 'Die while reloading 100 times', 'shame', 5, 'shame_reload', 'reload_deaths', 100, 10],
    ['spawn_victim', 'Spawn Victim', 'Get spawn killed 50 times', 'shame', 5, 'shame_spawn', 'spawn_deaths', 50, 10],
    
    // ==================== TIER 6: MAP & WORLD ====================
    ['tourist', 'Tourist', 'Play on 10 different maps', 'map', 6, 'map_tourist', 'unique_maps', 10, 25],
    ['world_traveler', 'World Traveler', 'Play on 25 different maps', 'map', 6, 'map_traveler', 'unique_maps', 25, 50],
    ['door_opener', 'Door Opener', 'Open 100 doors', 'map', 6, 'door', 'doors_opened', 100, 10],
    ['door_buster', 'Door Buster', 'Open 1,000 doors', 'map', 6, 'door_gold', 'doors_opened', 1000, 50],
    ['window_smasher', 'Window Smasher', 'Break 100 windows', 'map', 6, 'window', 'windows_broken', 100, 10],
    ['ladder_master', 'Ladder Master', 'Climb ladders 500 times', 'map', 6, 'ladder', 'ladder_climbs', 500, 25],
    ['marathon_runner', 'Marathon Runner', 'Travel 100km total distance', 'map', 6, 'distance', 'distance_100km', 1, 50],
    ['sewer_rat', 'Sewer Rat', 'Spend 1 hour in sewers/underground areas', 'map', 6, 'sewer', 'underground_time', 3600, 25],
    
    // ==================== TIER 7: DEDICATION ====================
    ['rookie', 'Rookie', 'Play for 1 hour', 'dedication', 7, 'time_bronze', 'playtime', 3600, 10],
    ['regular', 'Regular', 'Play for 10 hours', 'dedication', 7, 'time_silver', 'playtime', 36000, 25],
    ['dedicated', 'Dedicated', 'Play for 50 hours', 'dedication', 7, 'time_gold', 'playtime', 180000, 50],
    ['hardcore', 'Hardcore', 'Play for 100 hours', 'dedication', 7, 'time_platinum', 'playtime', 360000, 100],
    ['no_life', 'No Life', 'Play for 500 hours', 'dedication', 7, 'time_diamond', 'playtime', 1800000, 250],
    ['founder', 'Founder', 'Play during the first month of launch', 'dedication', 7, 'founder', 'founder', 1, 500],
    ['early_adopter', 'Early Adopter', 'One of the first 100 players to register', 'dedication', 7, 'early_adopter', 'early_adopter', 1, 250],
    
    // ==================== SECRET ACHIEVEMENTS ====================
    ['pacifist', 'Pacifist', 'Complete an entire match with 0 kills', 'secret', 8, 'secret_pacifist', 'match_no_kills', 1, 100],
    ['perfectionist', 'Perfectionist', 'Complete a match with 10+ kills and 0 deaths', 'secret', 8, 'secret_perfect', 'perfect_match', 1, 200],
    ['revenge', 'REVENGE', 'Kill your nemesis 5 times in one match after they dominated you', 'secret', 8, 'secret_revenge', 'revenge_kill', 1, 100],
    ['comeback_kid', 'Comeback Kid', 'Win a match after being down 5+ rounds', 'secret', 8, 'secret_comeback', 'comeback_win', 1, 150],
    
    // ============================================================
    // =========== SABERMETRICS ACHIEVEMENTS (Advanced) ===========
    // ============================================================
    
    // ==================== HITBOX MASTERY ====================
    ['the_surgeon', 'The Surgeon', 'Land 10 consecutive headshots without missing', 'hitbox', 5, 'surgeon', 'consecutive_headshots', 10, 300],
    ['ankle_biter', 'Ankle Biter', 'Get 3 kills in one life hitting only legs/feet (Hitbox 13-18)', 'hitbox', 4, 'ankle_biter', 'leg_only_kills_streak', 3, 150],
    ['groin_inator', 'The Groin-inator', 'Accumulate 50 kills via pelvis shots (Hitbox 6)', 'hitbox', 4, 'groin', 'pelvis_kills', 50, 200],
    ['helmet_popper', 'Helmet Popper', 'Hit 100 helmet dinks without the kill shot (Hitbox 1)', 'hitbox', 3, 'helmet', 'helmet_dinks', 100, 125],
    ['phantom_limb', 'Phantom Limb', 'Die from damage dealt to your hand (Hitbox 15-16)', 'hitbox', 2, 'phantom', 'hand_deaths', 1, 50],
    ['torso_terrorist', 'Torso Terrorist', 'Get 500 upper body kills (Hitbox 2-5)', 'hitbox', 4, 'torso', 'upper_body_kills', 500, 175],
    ['full_body_scan', 'Full Body Scan', 'Hit all 19 hitboxes in a single match', 'hitbox', 5, 'full_scan', 'unique_hitboxes_match', 19, 250],
    ['precision_strike', 'Precision Strike', 'Maintain 80% headshot ratio over 50 kills', 'hitbox', 6, 'precision', 'headshot_ratio_sustained', 80, 400],
    
    // ==================== MOVEMENT ANALYTICS ====================
    ['grass_snake', 'Grass Snake', 'Get 10 kills while prone in a single match', 'movement', 3, 'snake', 'prone_kills_match', 10, 100],
    ['the_rabbit', 'The Rabbit', 'Execute 500 jumps in a single match', 'movement', 2, 'rabbit', 'jumps_match', 500, 75],
    ['the_statue', 'The Statue', 'Win a round without moving (0 distance traveled)', 'movement', 5, 'statue', 'stationary_round_win', 1, 300],
    ['air_jordan', 'Air Jordan', 'Get a kill while you are mid-air (Z velocity > 100)', 'movement', 4, 'airjordan', 'airborne_kills', 1, 150],
    ['crouch_tiger', 'Crouch Tiger', 'Kill 3 sprinting enemies while crouching', 'movement', 4, 'crouch', 'crouch_sprint_kills', 3, 125],
    ['marathon_distance', 'Marathon Man', 'Travel 42,195 units in a single match (marathon)', 'movement', 3, 'marathon', 'distance_match', 42195, 100],
    ['bunny_hopper', 'Bunny Hopper', 'Maintain air time for 3+ seconds while killing', 'movement', 5, 'bunny', 'extended_air_kill', 1, 200],
    ['slide_master', 'Slide Master', 'Get 25 kills while sliding/momentum moving', 'movement', 4, 'slide', 'momentum_kills', 25, 150],
    ['prone_warrior', 'Prone Warrior', 'Spend 50% of a match prone and win', 'movement', 3, 'prone', 'prone_time_win', 50, 100],
    ['vertical_threat', 'Vertical Threat', 'Get kills from 3 different elevation levels in one life', 'movement', 4, 'vertical', 'elevation_kills_life', 3, 125],
    
    // ==================== ADVANCED WEAPON BADGES ====================
    ['rocket_sniper', 'Rocket Sniper', 'Get a direct impact Bazooka kill from >500 units', 'weapon', 5, 'rocket_sniper', 'direct_rocket_distance', 500, 250],
    ['kobe', 'KOBE!', 'Get a grenade kill from >2000 units distance', 'weapon', 5, 'kobe', 'grenade_distance_kill', 2000, 300],
    ['martyr', 'Martyr', 'Get a kill with your own grenade after you die', 'weapon', 4, 'martyr', 'posthumous_grenade_kills', 1, 150],
    ['click_click', 'Click... Click...', 'Die while in the middle of a reload animation', 'shame', 2, 'click', 'reload_deaths', 1, 25],
    ['lumberjack', 'Lumberjack', 'Get 5 melee/bash kills in a single match', 'weapon', 3, 'lumberjack', 'bash_kills_match', 5, 100],
    ['one_clip_wonder', 'One Clip Wonder', 'Kill 3 enemies without reloading', 'weapon', 4, 'oneclip', 'kills_without_reload', 3, 150],
    ['spray_pray', 'Spray & Pray', 'Get a kill with <5% accuracy in a firefight', 'weapon', 2, 'spray', 'low_accuracy_kill', 1, 50],
    ['quickdraw', 'Quickdraw McGraw', 'Kill an enemy within 0.5s of weapon switch', 'weapon', 4, 'quickdraw', 'quickswitch_kills', 10, 150],
    ['ammo_hoarder', 'Ammo Hoarder', 'Finish a match with >90% ammo remaining', 'troll', 2, 'hoarder', 'ammo_hoarding', 1, 50],
    ['trigger_discipline', 'Trigger Discipline', 'Get 10 kills with only 10 bullets fired', 'hardcore', 6, 'trigger', 'bullet_efficiency', 100, 400],
    
    // ==================== OBJECTIVE BADGES ====================
    ['ninja_defuse', 'Ninja Defuse', 'Defuse bomb with 3+ enemies still alive nearby', 'objective', 5, 'ninja', 'ninja_defuses_alive', 1, 300],
    ['buzzer_beater', 'Buzzer Beater', 'Defuse bomb with <0.5 seconds remaining', 'objective', 5, 'buzzer', 'clutch_defuses', 1, 250],
    ['postal_service', 'Postal Service', 'Capture flag without taking any damage', 'objective', 4, 'postal', 'clean_flag_caps', 1, 175],
    ['designated_driver', 'Designated Driver', 'Stay in a vehicle for 5+ minutes without dying', 'objective', 3, 'driver', 'vehicle_time', 300, 100],
    ['gate_crasher', 'Gate Crasher', 'Plant bomb within 30 seconds of round start', 'objective', 4, 'gatecrasher', 'fast_plants', 10, 150],
    ['last_stand', 'Last Stand', 'Win a 1v4+ clutch situation', 'objective', 6, 'laststand', 'clutch_1v4', 1, 400],
    ['objective_whore', 'Objective Specialist', 'Complete 100 objective actions', 'objective', 4, 'objective', 'total_objectives', 100, 175],
    
    // ==================== SITUATIONAL BADGES ====================
    ['the_janitor', 'The Janitor', 'Get 3 kills while having <10 HP', 'situational', 4, 'janitor', 'low_hp_kills', 3, 150],
    ['human_shield', 'Human Shield', 'Absorb 500+ damage in a single round and survive', 'situational', 4, 'shield', 'damage_absorbed_round', 500, 175],
    ['kenny', 'They Killed Kenny!', 'Die first in 5 consecutive rounds', 'shame', 2, 'kenny', 'first_death_streak', 5, 25],
    ['carry_lord', 'Carry Lord', 'Have double the score of the 2nd best player', 'situational', 5, 'carry', 'carry_ratio', 2, 250],
    ['bot_mode', 'Bot Mode', 'Finish a match with 0 kills and 10+ deaths', 'shame', 1, 'bot', 'zero_kill_deaths', 10, 10],
    ['avenger', 'Avenger', 'Kill your killer within 5 seconds of respawning', 'situational', 3, 'avenger', 'revenge_kills_fast', 10, 100],
    ['bodyguard', 'Bodyguard', 'Kill an enemy aiming at your flag carrier', 'situational', 4, 'bodyguard', 'carrier_saves', 5, 150],
    ['rage_quit_causer', 'Rage Quit Causer', 'Kill a player who quits within 30s of death', 'troll', 3, 'ragequit', 'rage_disconnects', 5, 125],
    ['kd_comeback', 'K/D Comeback', 'Go from worst to best K/D in a match', 'situational', 4, 'comeback', 'kd_turnaround', 1, 175],
    ['untouchable', 'Untouchable', 'Get 10+ kills without taking any damage', 'hardcore', 5, 'untouchable', 'flawless_killstreak', 10, 300],
    
    // ==================== PHYSICS BADGES ====================
    ['isaac_newton', 'Isaac Newton', 'Kill by knocking someone off a height with damage', 'physics', 5, 'newton', 'knockback_fall_kills', 1, 250],
    ['telefrag', 'Telefrag', 'Spawn directly inside another player', 'physics', 6, 'telefrag', 'spawn_kills', 1, 350],
    ['crushed', 'Crushed', 'Die by a moving door, tank, or physics object', 'physics', 3, 'crushed', 'crush_deaths', 1, 75],
    ['wallbanger', 'Wallbanger', 'Kill 2 enemies with one bullet through a wall', 'physics', 5, 'wallbang', 'penetration_multikill', 1, 250],
    ['collateral_sniper', 'Collateral Damage', 'Get 2 kills with a single sniper bullet', 'physics', 5, 'collateral', 'sniper_collateral', 1, 225],
    ['ricochet_rick', 'Ricochet Rick', 'Get a kill with a ricocheted bullet', 'physics', 6, 'ricochet', 'ricochet_kills', 1, 400],
    ['gravity_well', 'Gravity Well', 'Kill 3 enemies who are falling', 'physics', 4, 'gravity', 'falling_target_kills', 3, 150],
    ['bank_shot', 'Bank Shot', 'Kill with a grenade that bounced 3+ times', 'physics', 4, 'bankshot', 'bounced_grenade_kills', 1, 175],
    ['trajectory_master', 'Trajectory Master', 'Kill with projectile traveling >3 seconds', 'physics', 5, 'trajectory', 'long_flight_kills', 1, 250],
    
    // ==================== HARDCORE / PRO BADGES ====================
    ['no_scope_360', '360 No Scope', 'No-scope sniper kill while rotating >270 degrees', 'hardcore', 6, 'noscope360', 'rotation_noscope', 1, 500],
    ['pistol_perfect', 'Pistol Perfect', 'Win a match using only pistol with 15+ kills', 'hardcore', 5, 'pistol', 'pistol_only_match', 15, 300],
    ['iron_man', 'Iron Man', 'Complete 10 matches without dying', 'hardcore', 7, 'ironman', 'deathless_matches', 10, 750],
    ['aimbot_accusation', 'Totally Not Cheating', 'Maintain 90%+ headshot ratio for 20+ kills', 'hardcore', 6, 'aimbot', 'suspicious_accuracy', 1, 400],
    ['the_wall', 'The Wall', 'Block 1000 damage as the team tank', 'hardcore', 4, 'wall', 'damage_blocked', 1000, 175],
    ['sniper_elite', 'Sniper Elite', 'Get 10 kills from >1000 units without being spotted', 'hardcore', 5, 'sniperelite', 'long_range_stealth', 10, 300],
    ['one_man_army', 'One Man Army', 'Single-handedly kill entire enemy team in one round', 'hardcore', 6, 'onemanarrmy', 'team_wipe_solo', 1, 450],
    ['flawless_victory', 'Flawless Victory', 'Win a round taking 0 damage with 5+ kills', 'hardcore', 6, 'flawless', 'perfect_round', 1, 400],
    ['on_fire', 'On Fire', 'Get 10 kills in 60 seconds', 'hardcore', 5, 'onfire', 'kills_per_minute', 10, 275],
    ['killionaire', 'Killionaire', 'Get 25 kills without dying', 'hardcore', 6, 'killionaire', 'killstreak_25', 1, 500],
    
    // ==================== FUN / TROLL BADGES ====================
    ['friendly_fire', 'Friendly Fire Enthusiast', 'Deal 500 team damage across career (shame!)', 'shame', 1, 'teamkill', 'team_damage', 500, 5],
    ['cliff_diver', 'Cliff Diver', 'Die from fall damage 10 times', 'troll', 2, 'cliff', 'fall_deaths', 10, 25],
    ['lemming', 'Lemming', 'Follow a teammate off a cliff to death', 'troll', 2, 'lemming', 'follow_fall_deaths', 1, 50],
    ['own_worst_enemy', 'Own Worst Enemy', 'Kill yourself 25 times (grenades, rockets, falls)', 'shame', 2, 'suicide', 'suicides', 25, 25],
    ['decoy', 'Human Decoy', 'Die 10 times without getting a kill', 'troll', 1, 'decoy', 'decoy_deaths', 10, 15],
    ['camping_license', 'Camping License', 'Spend 80% of a match within 50 units radius', 'troll', 2, 'camping', 'camping_detected', 1, 50],
    ['tourist', 'Tourist', 'Visit every spawn point on a map without dying', 'troll', 3, 'tourist', 'spawn_tour', 1, 100],
    ['conscientious_objector', 'Conscientious Objector', 'Complete a full match with 0 shots fired', 'troll', 4, 'objector', 'zero_shots_match', 1, 150],
    ['pacifist_round', 'The Pacifist', 'Win a round dealing 0 damage to enemies', 'troll', 5, 'pacifist', 'zero_damage_round_win', 1, 250],
    
    // ==================== MAP MASTERY EXTENDED ====================
    ['world_traveler', 'World Traveler', 'Play on every map in the rotation', 'map', 3, 'traveler', 'unique_maps_played', 20, 100],
    ['home_turf', 'Home Turf', 'Win 100 matches on your most-played map', 'map', 5, 'hometurf', 'favorite_map_wins', 100, 250],
    ['cartographer', 'Cartographer', 'Discover every corner of a map (95% coverage)', 'map', 4, 'cartographer', 'map_coverage', 95, 175],
    
    // ==================== TIME-BASED BADGES ====================
    ['night_owl', 'Night Owl', 'Play 100 matches between midnight and 6 AM', 'dedication', 3, 'nightowl', 'night_matches', 100, 100],
    ['weekend_warrior', 'Weekend Warrior', 'Play 500 matches on weekends', 'dedication', 4, 'weekend', 'weekend_matches', 500, 150],
    ['early_bird', 'Early Bird', 'Be the first player to get a kill in 50 matches', 'situational', 3, 'earlybird', 'first_bloods', 50, 100],
    ['overtime_hero', 'Overtime Hero', 'Win 10 matches that went to overtime', 'situational', 4, 'overtime', 'overtime_wins', 10, 150],
    
    // ==================== STREAK BADGES EXTENDED ====================
    ['double_down', 'Double Down', 'Get 2 double kills in one life', 'situational', 3, 'doubledown', 'double_kills_life', 2, 100],
    ['overkill', 'Overkill', 'Get a kill with >200% overkill damage', 'weapon', 3, 'overkill', 'overkill_damage', 1, 75],
    ['glass_cannon', 'Glass Cannon', 'Get 20 kills and 20 deaths in one match', 'situational', 2, 'glass', 'high_activity_match', 1, 50],
    ['immortal', 'Immortal', 'Play a full match without dying (min 10 min)', 'hardcore', 6, 'immortal', 'deathless_full_match', 1, 400],
    ['respawn_king', 'Respawn King', 'Respawn 50 times in a single match', 'shame', 2, 'respawn', 'respawns_match', 50, 25],
    ['spawn_trapped', 'Spawn Trapped', 'Die within 3 seconds of spawning 5 times', 'shame', 2, 'spawntrap', 'instant_deaths', 5, 25],
    
    // ==================== VECTOR MATH BADGES ====================
    ['right_angle', 'Right Angle', 'Kill perpendicular to their facing (90 degrees)', 'physics', 4, 'rightangle', 'perpendicular_kills', 10, 150],
    ['face_to_face', 'Face to Face', 'Win a mutual duel (both aiming at each other)', 'situational', 3, 'faceto', 'duel_kills', 25, 100],
    ['backstab_angle', 'Backstab', 'Kill 50 enemies from behind (>135 deg from facing)', 'tactical', 3, 'backstab2', 'behind_kills', 50, 100],
];

foreach ($achievements as $a) {
    $smcFunc['db_insert']('ignore',
        '{db_prefix}mohaa_achievement_defs',
        [
            'code' => 'string',
            'name' => 'string',
            'description' => 'string',
            'category' => 'string',
            'tier' => 'int',
            'icon' => 'string',
            'requirement_type' => 'string',
            'requirement_value' => 'int',
            'points' => 'int',
        ],
        [
            $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8]
        ],
        ['id_achievement']
    );
}
