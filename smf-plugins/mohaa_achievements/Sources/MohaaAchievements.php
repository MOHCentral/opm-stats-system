<?php
/**
 * MOHAA Achievements & Medals Plugin
 * 
 * Comprehensive achievement system with tiered badges and medals
 *
 * @package MohaaAchievements
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaAchievements_Actions(array &$actions): void
{
    $actions['mohaachievements'] = ['MohaaAchievements.php', 'MohaaAchievements_Main'];
}

/**
 * Add profile areas
 */
function MohaaAchievements_ProfileAreas(array &$profile_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaabadges'] = [
        'label' => $txt['mohaa_medals_badges'] ?? 'Medals & Badges',
        'file' => 'MohaaAchievements.php',
        'function' => 'MohaaAchievements_ProfileMedals',
        'icon' => 'members',
    ];
}

/**
 * Main dispatcher
 */
function MohaaAchievements_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaAchievements');
    
    $subActions = [
        'list' => 'MohaaAchievements_List',
        'view' => 'MohaaAchievements_View',
        'leaderboard' => 'MohaaAchievements_Leaderboard',
        'recent' => 'MohaaAchievements_Recent',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    call_user_func($subActions[$sa]);
}

/**
 * List all achievements
 */
function MohaaAchievements_List(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $context['page_title'] = $txt['mohaa_achievements'] ?? 'Achievements';
    $context['sub_template'] = 'mohaa_achievements_list';
    
    // Initialize API
    require_once(__DIR__ . '/../MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Get all achievement definitions from API
    $apiAchievements = $api->getAchievements() ?? [];
    $achievements = [];
    foreach ($apiAchievements as $a) {
        $achievements[$a['id_achievement']] = $a;
    }
    
    // Get user's GUID and progress
    $unlocked = [];
    $progress = [];
    
    if (!$user_info['is_guest']) {
        // Get GUID
        $request = $smcFunc['db_query']('', '
            SELECT player_guid FROM {db_prefix}mohaa_identities 
            WHERE id_member = {int:member} LIMIT 1',
            ['member' => $user_info['id']]
        );
        $row = $smcFunc['db_fetch_assoc']($request);
        $smcFunc['db_free_result']($request);
        
        if ($row && !empty($row['player_guid'])) {
            $playerData = $api->getPlayerAchievements($row['player_guid']);
            
            // Map unlocked
            if (!empty($playerData['unlocked'])) {
                foreach ($playerData['unlocked'] as $u) {
                    $unlocked[$u['id_achievement']] = $u;
                }
            }
            // Map progress
            if (!empty($playerData['progress'])) {
                foreach ($playerData['progress'] as $p) {
                    $progress[$p['id_achievement']] = $p['current_progress'];
                }
            }
        }
    }
    
    // Group by category
    $categories = [
        'basic' => ['name' => 'Basic Training', 'tier' => 1, 'style' => 'bronze'],
        'weapon' => ['name' => 'Weapon Specialist', 'tier' => 2, 'style' => 'silver'],
        'tactical' => ['name' => 'Tactical & Skill', 'tier' => 3, 'style' => 'gold'],
        'humiliation' => ['name' => 'Humiliation', 'tier' => 4, 'style' => 'patch'],
        'shame' => ['name' => 'Hall of Shame', 'tier' => 5, 'style' => 'rusty'],
        'map' => ['name' => 'Map & World', 'tier' => 6, 'style' => 'stamp'],
        'dedication' => ['name' => 'Dedication', 'tier' => 7, 'style' => 'trophy'],
        'secret' => ['name' => 'Secret', 'tier' => 8, 'style' => 'secret'],
    ];
    
    $grouped = [];
    foreach ($categories as $cat => $info) {
        $grouped[$cat] = [
            'info' => $info,
            'achievements' => [],
        ];
    }
    
    foreach ($achievements as $id => $ach) {
        $cat = $ach['category'];
        if (!isset($grouped[$cat])) continue;
        
        $ach['is_unlocked'] = isset($unlocked[$id]);
        $ach['unlocked_date'] = $unlocked[$id]['unlocked_date'] ?? null;
        $ach['current_progress'] = $progress[$id] ?? 0;
        $ach['progress_percent'] = min(100, round(($ach['current_progress'] / max(1, $ach['requirement_value'])) * 100));
        
        $grouped[$cat]['achievements'][] = $ach;
    }
    
    // Stats summary
    $totalAchievements = count($achievements);
    $unlockedCount = count($unlocked);
    $totalPoints = 0;
    $earnedPoints = 0;
    
    foreach ($achievements as $ach) {
        $totalPoints += $ach['points'];
        if (isset($unlocked[$ach['id_achievement']])) {
            $earnedPoints += $ach['points'];
        }
    }
    
    $context['mohaa_achievements'] = [
        'categories' => $grouped,
        'total' => $totalAchievements,
        'unlocked' => $unlockedCount,
        'total_points' => $totalPoints,
        'earned_points' => $earnedPoints,
        'completion_percent' => round(($unlockedCount / max(1, $totalAchievements)) * 100),
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaachievements',
        'name' => $txt['mohaa_achievements'] ?? 'Achievements',
    ];
}

/**
 * View single achievement details
 */
function MohaaAchievements_View(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaachievements');
        return;
    }
    
    // Initialize API
    require_once(__DIR__ . '/../MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $achievement = $api->getAchievement($id);
    
    if (!$achievement) {
        fatal_lang_error('mohaa_achievement_not_found', false);
        return;
    }
    
    $context['page_title'] = $achievement['name'];
    $context['sub_template'] = 'mohaa_achievement_view';
    
    // Players list - currently not supported by API, using empty list
    // TODO: Add endpoint for achievement unlockers
    $players = []; 
    $totalUnlocks = 0; // Placeholder
    
    $context['mohaa_achievement'] = [
        'info' => $achievement,
        'players' => $players,
        'total_unlocks' => $totalUnlocks,
    ];
}

/**
 * Achievement leaderboard
 */
function MohaaAchievements_Leaderboard(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Achievement Leaderboard';
    $context['sub_template'] = 'mohaa_achievements_leaderboard';
    
    // Initialize API
    require_once(__DIR__ . '/../MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $players = $api->getAchievementLeaderboard() ?? [];
    // Mock mapping if API returns different structure or empty
    if (empty($players)) $players = [];
    
    $context['mohaa_leaderboard'] = $players;
}

/**
 * Recent achievements
 */
function MohaaAchievements_Recent(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = 'Recent Achievements';
    $context['sub_template'] = 'mohaa_achievements_recent';
    
    // Initialize API
    require_once(__DIR__ . '/../MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $recent = $api->getRecentAchievements() ?? [];
    
    $context['mohaa_recent'] = $recent;
}

/**
 * Profile medals page
 */



/**
 * Profile medals page
 */
function MohaaAchievements_ProfileMedals(int $memID): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    loadTemplate('MohaaAchievements');
    
    $context['page_title'] = 'Medals & Badges';
    $context['sub_template'] = 'mohaa_profile_medals';
    
    // Get member info
    $request = $smcFunc['db_query']('', '
        SELECT member_name, real_name FROM {db_prefix}members WHERE id_member = {int:id}',
        ['id' => $memID]
    );
    $member = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Get GUID
    $request = $smcFunc['db_query']('', '
        SELECT player_guid FROM {db_prefix}mohaa_identities 
        WHERE id_member = {int:id}',
        ['id' => $memID]
    );
    $identity = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    $achievements = [];
    $totalPoints = 0;
    
    if ($identity && !empty($identity['player_guid'])) {
        // Initialize API
        require_once(__DIR__ . '/../MohaaStats/MohaaStatsAPI.php');
        $api = new MohaaStatsAPIClient();
        
        $playerData = $api->getPlayerAchievements($identity['player_guid']);
        if (!empty($playerData['unlocked'])) {
            $achievements = $playerData['unlocked'];
            
            // Calculate points and get definitions if needed
            // API returns full achievement objects in 'unlocked'
            foreach ($achievements as $a) {
                $totalPoints += ($a['points'] ?? 0);
            }
        }
    }
    
    // Get featured (highest tier) achievements
    // Sort by tier desc, date desc
    usort($achievements, function($a, $b) {
        if (($b['tier'] ?? 0) != ($a['tier'] ?? 0)) {
            return ($b['tier'] ?? 0) <=> ($a['tier'] ?? 0);
        }
        return ($b['unlocked_date'] ?? 0) <=> ($a['unlocked_date'] ?? 0);
    });
    
    $featured = array_slice($achievements, 0, 5);
    
    $context['mohaa_profile_medals'] = [
        'member_id' => $memID,
        'member_name' => $member['real_name'] ?: $member['member_name'],
        'achievements' => $achievements,
        'featured' => $featured,
        'total_points' => $totalPoints,
        'count' => count($achievements),
        'is_own' => $memID == $user_info['id'],
    ];
}

/**
 * Award an achievement to a player (called by API/backend)
 */
function MohaaAchievements_Award(int $memberId, string $achievementCode, ?string $matchId = null): bool
{
    global $smcFunc;
    
    // Get achievement by code
    $request = $smcFunc['db_query']('', '
        SELECT id_achievement FROM {db_prefix}mohaa_achievement_defs
        WHERE code = {string:code}',
        ['code' => $achievementCode]
    );
    
    $achievement = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$achievement) {
        return false;
    }
    
    // Check if already unlocked
    $request = $smcFunc['db_query']('', '
        SELECT id_unlock FROM {db_prefix}mohaa_player_achievements
        WHERE id_member = {int:member} AND id_achievement = {int:achievement}',
        ['member' => $memberId, 'achievement' => $achievement['id_achievement']]
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        return false; // Already unlocked
    }
    $smcFunc['db_free_result']($request);
    
    // Award the achievement
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_player_achievements',
        [
            'id_member' => 'int',
            'id_achievement' => 'int',
            'unlocked_date' => 'int',
            'match_id' => 'string',
        ],
        [
            $memberId,
            $achievement['id_achievement'],
            time(),
            $matchId ?? '',
        ],
        ['id_unlock']
    );
    
    return true;
}

/**
 * Update achievement progress
 */
function MohaaAchievements_UpdateProgress(int $memberId, int $achievementId, int $progress): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        INSERT INTO {db_prefix}mohaa_achievement_progress (id_member, id_achievement, current_progress, last_updated)
        VALUES ({int:member}, {int:achievement}, {int:progress}, {int:now})
        ON DUPLICATE KEY UPDATE current_progress = {int:progress}, last_updated = {int:now}',
        [
            'member' => $memberId,
            'achievement' => $achievementId,
            'progress' => $progress,
            'now' => time(),
        ]
    );
}
