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
    
    // Get all achievement definitions
    $achievements = [];
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_achievement_defs
        ORDER BY tier, category, sort_order, id_achievement',
        []
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $achievements[$row['id_achievement']] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get user's unlocked achievements
    $unlocked = [];
    if (!$user_info['is_guest']) {
        $request = $smcFunc['db_query']('', '
            SELECT id_achievement, unlocked_date, progress
            FROM {db_prefix}mohaa_player_achievements
            WHERE id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $unlocked[$row['id_achievement']] = $row;
        }
        $smcFunc['db_free_result']($request);
    }
    
    // Get progress for locked achievements
    $progress = [];
    if (!$user_info['is_guest']) {
        $request = $smcFunc['db_query']('', '
            SELECT id_achievement, current_progress
            FROM {db_prefix}mohaa_achievement_progress
            WHERE id_member = {int:member}',
            ['member' => $user_info['id']]
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $progress[$row['id_achievement']] = $row['current_progress'];
        }
        $smcFunc['db_free_result']($request);
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
    
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_achievement_defs
        WHERE id_achievement = {int:id}',
        ['id' => $id]
    );
    
    $achievement = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$achievement) {
        fatal_lang_error('mohaa_achievement_not_found', false);
        return;
    }
    
    $context['page_title'] = $achievement['name'];
    $context['sub_template'] = 'mohaa_achievement_view';
    
    // Get players who have unlocked this
    $players = [];
    $request = $smcFunc['db_query']('', '
        SELECT pa.*, m.member_name, m.real_name
        FROM {db_prefix}mohaa_player_achievements AS pa
        LEFT JOIN {db_prefix}members AS m ON pa.id_member = m.id_member
        WHERE pa.id_achievement = {int:id}
        ORDER BY pa.unlocked_date ASC
        LIMIT 50',
        ['id' => $id]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $players[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get total unlock count
    $request = $smcFunc['db_query']('', '
        SELECT COUNT(*) as total FROM {db_prefix}mohaa_player_achievements
        WHERE id_achievement = {int:id}',
        ['id' => $id]
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_achievement'] = [
        'info' => $achievement,
        'players' => $players,
        'total_unlocks' => $row['total'],
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
    
    // Get players by achievement points
    $players = [];
    $request = $smcFunc['db_query']('', '
        SELECT 
            pa.id_member,
            m.member_name,
            m.real_name,
            COUNT(pa.id_achievement) as achievement_count,
            SUM(ad.points) as total_points
        FROM {db_prefix}mohaa_player_achievements AS pa
        INNER JOIN {db_prefix}mohaa_achievement_defs AS ad ON pa.id_achievement = ad.id_achievement
        LEFT JOIN {db_prefix}members AS m ON pa.id_member = m.id_member
        GROUP BY pa.id_member
        ORDER BY total_points DESC
        LIMIT 100',
        []
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $players[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
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
    
    $recent = [];
    $request = $smcFunc['db_query']('', '
        SELECT pa.*, ad.name, ad.description, ad.icon, ad.category, ad.tier, ad.points,
               m.member_name, m.real_name
        FROM {db_prefix}mohaa_player_achievements AS pa
        INNER JOIN {db_prefix}mohaa_achievement_defs AS ad ON pa.id_achievement = ad.id_achievement
        LEFT JOIN {db_prefix}members AS m ON pa.id_member = m.id_member
        ORDER BY pa.unlocked_date DESC
        LIMIT 50',
        []
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $recent[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_recent'] = $recent;
}

/**
 * Profile medals page
 */
function MohaaAchievements_ProfileMedals(int $memID): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    loadTemplate('MohaaAchievements');
    
    $context['page_title'] = 'Medals & Badges';
    $context['sub_template'] = 'mohaa_profile_medals';
    
    // Get member's achievements
    $achievements = [];
    $request = $smcFunc['db_query']('', '
        SELECT pa.*, ad.*
        FROM {db_prefix}mohaa_player_achievements AS pa
        INNER JOIN {db_prefix}mohaa_achievement_defs AS ad ON pa.id_achievement = ad.id_achievement
        WHERE pa.id_member = {int:member}
        ORDER BY ad.tier DESC, pa.unlocked_date DESC',
        ['member' => $memID]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $achievements[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get total points
    $totalPoints = 0;
    foreach ($achievements as $a) {
        $totalPoints += $a['points'];
    }
    
    // Get member info
    $request = $smcFunc['db_query']('', '
        SELECT member_name, real_name FROM {db_prefix}members WHERE id_member = {int:id}',
        ['id' => $memID]
    );
    $member = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Get featured (highest tier) achievements
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
