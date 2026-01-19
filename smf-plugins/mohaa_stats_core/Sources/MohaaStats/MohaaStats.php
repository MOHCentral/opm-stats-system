<?php
/**
 * MOHAA Stats Core - Main Integration File
 * 
 * This is the core plugin that provides:
 * - API communication layer
 * - Caching infrastructure
 * - Admin configuration
 * - Base hooks for other MOHAA Stats plugins
 *
 * @package MohaaStats
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

// Load the API client class
require_once(__DIR__ . '/MohaaStatsAPI.php');

/**
 * Register actions (URL routes)
 */
function MohaaStats_Actions(array &$actions): void
{
    $actions['mohaastats'] = ['MohaaStats/MohaaStats.php', 'MohaaStats_Main'];
    $actions['mohaaapi'] = ['MohaaStats/MohaaStatsAPI.php', 'MohaaStats_APIProxy'];
}

/**
 * Add admin configuration areas
 */
function MohaaStats_AdminAreas(array &$admin_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $admin_areas['config']['areas']['mohaastats'] = [
        'label' => $txt['mohaa_stats_admin'],
        'file' => 'MohaaStats/MohaaStatsAdmin.php',
        'function' => 'MohaaStats_AdminConfig',
        'icon' => 'posts',
        'subsections' => [
            'general' => [$txt['mohaa_stats_general']],
            'api' => [$txt['mohaa_stats_api_settings']],
            'cache' => [$txt['mohaa_stats_cache']],
            'linking' => [$txt['mohaa_stats_linking']],
        ],
    ];
}

/**
 * Add menu buttons
 */
function MohaaStats_MenuButtons(array &$buttons): void
{
    global $txt, $scripturl, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    
    loadLanguage('MohaaStats');
    
    // Add main Stats menu item
    $buttons['mohaastats'] = [
        'title' => $txt['mohaa_stats'],
        'href' => $scripturl . '?action=mohaadashboard',
        'icon' => 'stats',
        'show' => true,
        'sub_buttons' => [
            'warroom' => [
                'title' => $txt['mohaa_war_room'] ?? 'War Room',
                'href' => $scripturl . '?action=mohaadashboard',
                'show' => true,
            ],
            'leaderboard' => [
                'title' => $txt['mohaa_leaderboard'] ?? 'Leaderboard',
                'href' => $scripturl . '?action=mohaastats;sa=leaderboards',
                'show' => true,
            ],
            'livematches' => [
                'title' => $txt['mohaa_live_matches'] ?? 'Live Matches',
                'href' => $scripturl . '?action=mohaastats;sa=live',
                'show' => true,
            ],
            'servers' => [
                'title' => $txt['mohaa_servers'] ?? 'Servers',
                'href' => $scripturl . '?action=mohaastats;sa=servers',
                'show' => true,
            ],
            'maps' => [
                'title' => $txt['mohaa_maps'] ?? 'Maps',
                'href' => $scripturl . '?action=mohaastats;sa=maps',
                'show' => true,
            ],
            'achievements' => [
                'title' => $txt['mohaa_achievements'] ?? 'Achievements',
                'href' => $scripturl . '?action=mohaaachievements',
                'show' => true,
            ],
            'tournaments' => [
                'title' => $txt['mohaa_tournaments'] ?? 'Tournaments',
                'href' => $scripturl . '?action=mohaatournaments',
                'show' => true,
            ],
            'teams' => [
                'title' => $txt['mohaa_teams'] ?? 'Teams',
                'href' => $scripturl . '?action=mohaateams',
                'show' => true,
            ],
            'identity' => [
                'title' => $txt['mohaa_identity'] ?? 'My Identity',
                'href' => $scripturl . '?action=mohaaidentity',
                'show' => true,
            ],
            'tokens' => [
                'title' => $txt['mohaa_tokens'] ?? 'Link Account',
                'href' => $scripturl . '?action=profile;area=mohaaidentity',
                'show' => true,
            ],
        ],
    ];
    
    // Reorder to put it after Home
    $temp = [];
    foreach ($buttons as $key => $button) {
        // Skip mohaastats since we'll add it right after home
        if ($key === 'mohaastats') continue;
        
        $temp[$key] = $button;
        if ($key === 'home') {
            $temp['mohaastats'] = $buttons['mohaastats'];
        }
    }
    $buttons = $temp;
}

/**
 * Load theme resources
 */
function MohaaStats_LoadTheme(): void
{
    global $modSettings, $context, $settings;
    
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    
    // Load CSS
    loadCSSFile('mohaa_stats.css', ['default_theme' => true, 'minimize' => true]);
    
    // Load JavaScript
    loadJavaScriptFile('mohaa_stats.js', ['default_theme' => true, 'minimize' => true]);
    
    // Add API base URL for JavaScript
    addInlineJavaScript('
        window.MohaaStats = window.MohaaStats || {};
        window.MohaaStats.apiUrl = ' . JavaScriptEscape($modSettings['mohaa_stats_api_url'] ?? '') . ';
        window.MohaaStats.cacheTime = ' . (int)($modSettings['mohaa_stats_cache_duration'] ?? 60) . ';
    ', true);
}

/**
 * Main stats page dispatcher
 */
function MohaaStats_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    
    // Load appropriate template based on sub-action
    $templateMap = [
        'main' => 'MohaaStats',
        'leaderboards' => 'MohaaStatsLeaderboard',
        'leaderboard' => 'MohaaStatsLeaderboard',
        'weapons' => 'MohaaStatsLeaderboard',
        'player' => 'MohaaStatsPlayer',
        'matches' => 'MohaaStats',
        'match' => 'MohaaStatsMatch',
        'maps' => 'MohaaStatsLeaderboard',
        'map' => 'MohaaStatsLeaderboard',
        'servers' => 'MohaaServerStats', // New template
        'live' => 'MohaaStats',
        'link' => 'MohaaStatsPlayer',
        'token' => 'MohaaStatsPlayer',
        'generate_claim' => 'MohaaStatsPlayer',
        'generate_token' => 'MohaaStatsPlayer',
    ];
    
    $subActions = [
        'main' => 'MohaaStats_MainPage',
        'leaderboards' => 'MohaaStats_Leaderboards',
        'leaderboard' => 'MohaaStats_Leaderboards',
        'weapons' => 'MohaaStats_WeaponLeaderboard',
        'player' => 'MohaaStats_Player',
        'matches' => 'MohaaStats_Matches',
        'match' => 'MohaaStats_MatchDetail',
        'maps' => 'MohaaStats_MapLeaderboard',
        'map' => 'MohaaStats_MapDetail',
        'servers' => 'MohaaStats_ServerDashboard', // New function
        'live' => 'MohaaStats_Live',
        'link' => 'MohaaStats_LinkIdentity',
        'token' => 'MohaaStats_GenerateToken',
        'generate_claim' => 'MohaaStats_HandleClaim',
        'generate_token' => 'MohaaStats_HandleToken',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'main';
    
    // Load the right template
    loadTemplate($templateMap[$sa] ?? 'MohaaStats');
    
    call_user_func($subActions[$sa]);
}

/**
 * Dashboard/Main stats page
 */
function MohaaStats_MainPage(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_stats'];
    $context['sub_template'] = 'mohaa_stats_main';
    
    // Fetch dashboard data
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_stats'] = [
        'global' => $api->getGlobalStats(),
        'top_players' => $api->getLeaderboard('kills', 10),
        'recent_matches' => $api->getRecentMatches(5),
        'live_matches' => $api->getLiveMatches(),
    ];
}

function MohaaStats_Leaderboards(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_leaderboards'] ?? 'Leaderboards';
    $context['sub_template'] = 'mohaa_stats_leaderboard';
    
    $stat = isset($_GET['stat']) ? $_GET['stat'] : 'kills';
    $period = isset($_GET['period']) ? $_GET['period'] : 'all';
    $page = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $limit = 25;
    
    $api = new MohaaStatsAPIClient();
    
    // API returns {"players": [...], "total": N, "page": N}
    $apiResponse = $api->getLeaderboard($stat, $limit, $page, $period);
    
    $context['mohaa_leaderboard'] = [
        'stat' => $stat,
        'period' => $period,
        'players' => $apiResponse['players'] ?? [],
        'total' => $apiResponse['total'] ?? 0,
    ];
    
    // Pagination
    $context['page_index'] = constructPageIndex(
        $scripturl . '?action=mohaastats;sa=leaderboards;stat=' . $stat . ';period=' . $period,
        $page,
        $context['mohaa_leaderboard']['total'],
        $limit
    );
}

/**
 * Player stats page
 */
/**
 * Player stats page
 */
function MohaaStats_Player(): void
{
    global $context, $txt, $scripturl;
    
    $guid = isset($_GET['guid']) ? $_GET['guid'] : '';
    
    if (empty($guid)) {
        redirectexit('action=mohaastats;sa=leaderboards');
        return;
    }
    
    $api = new MohaaStatsAPIClient();
    
    // Parallel Fetching for Speed
    $requests = [
        'info' => ['endpoint' => '/stats/player/' . urlencode($guid)],
        'deep' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/deep'],
        'playstyle' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/playstyle'],
        'weapons' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/weapons'],
        'matches' => ['endpoint' => '/stats/player/' . urlencode($guid) . '/matches', 'params' => ['limit' => 10]],
        'achievements' => ['endpoint' => '/achievements/player/' . urlencode($guid)],
    ];
    
    $results = $api->getMultiple($requests);
    $player = $results['info'];
    
    if (empty($player)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    $context['page_title'] = sprintf($txt['mohaa_player_title'], $player['name']);
    $context['sub_template'] = 'mohaa_war_room'; // Use Unified War Room Template
    
    // Flatten Deep Stats for Template Compatibility
    $deep = $results['deep'] ?? [];
    $flatStats = array_merge(
        $player,
        $deep['combat'] ?? [],
        $deep['movement'] ?? [],
        $deep['accuracy'] ?? [],
        $deep['session'] ?? [],
        $deep['rivals'] ?? [],
        $deep['stance'] ?? []
    );
    
    // Add Structured Data
    $flatStats['weapons'] = $results['weapons'] ?? []; // Template handles array/object
    $flatStats['matches'] = $results['matches']['list'] ?? [];
    $flatStats['recent_matches'] = $results['matches']['list'] ?? [];
    $flatStats['playstyle'] = $results['playstyle'] ?? [];
    $flatStats['achievements'] = $results['achievements'] ?? [];
    
    $context['mohaa_dashboard'] = [
        'player_stats' => $flatStats,
        'member' => [
            'member_name' => $player['name'],
            'real_name' => $player['name'],
            'id' => 0 // External player
        ],
        'is_own' => MohaaStats_IsLinkedToUser($guid)
    ];
}

/**
 * Match list page
 */
function MohaaStats_Matches(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_matches'];
    $context['sub_template'] = 'mohaa_matches';
    
    $page = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $limit = 20;
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_matches'] = [
        'list' => $api->getRecentMatches($limit, $page),
        'total' => $api->getMatchCount(),
    ];
    
    $context['page_index'] = constructPageIndex(
        $scripturl . '?action=mohaastats;sa=matches',
        $page,
        $context['mohaa_matches']['total'],
        $limit
    );
}

/**
 * Match detail page
 */
function MohaaStats_MatchDetail(): void
{
    global $context, $txt;
    
    $matchId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($matchId)) {
        redirectexit('action=mohaastats;sa=matches');
        return;
    }
    
    $api = new MohaaStatsAPIClient();
    $match = $api->getMatchReport($matchId);
    
    if (empty($match)) {
        fatal_lang_error('mohaa_match_not_found', false);
        return;
    }
    
    // Fetch visual heatmap data separately
    $match['heatmap_data'] = [
        'kills' => $api->getMatchHeatmap($matchId, 'kills'),
        'deaths' => $api->getMatchHeatmap($matchId, 'deaths')
    ];
    
    $context['page_title'] = sprintf($txt['mohaa_match_title'], $match['info']['map_name'] ?? 'Unknown');
    $context['sub_template'] = 'mohaa_match_detail';
    
    $context['mohaa_match'] = $match;
}

/**
 * Maps page
 */
function MohaaStats_Maps(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_maps'];
    $context['sub_template'] = 'mohaa_maps';
    
    $api = new MohaaStatsAPIClient();
    $context['mohaa_maps'] = $api->getMapStats();
}

/**
 * Map detail with heatmap
 */
function MohaaStats_MapDetail(): void
{
    global $context, $txt;
    
    $mapId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($mapId)) {
        redirectexit('action=mohaastats;sa=maps');
        return;
    }
    
    $api = new MohaaStatsAPIClient();
    $map = $api->getMapDetails($mapId);
    
    if (empty($map)) {
        fatal_lang_error('mohaa_map_not_found', false);
        return;
    }
    
    $context['page_title'] = $map['name'];
    $context['sub_template'] = 'mohaa_map_detail';
    
    $context['mohaa_map'] = [
        'info' => $map,
        'heatmap_kills' => $api->getMapHeatmap($mapId, 'kills'),
        'heatmap_deaths' => $api->getMapHeatmap($mapId, 'deaths'),
    ];
}

/**
 * Live matches page
 */
function MohaaStats_Live(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_live'];
    $context['sub_template'] = 'mohaa_live';
    
    $api = new MohaaStatsAPIClient();
    $context['mohaa_live_matches'] = $api->getLiveMatches();
}

/**
 * Identity linking page
 */
function MohaaStats_LinkIdentity(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_link_identity'];
    $context['sub_template'] = 'mohaa_link_identity';
    
    // Get existing linked identities
    $context['mohaa_identities'] = MohaaStats_GetUserIdentities($user_info['id']);
    
    // Handle form submission
    if (isset($_POST['action_type'])) {
        checkSession();
        
        if ($_POST['action_type'] === 'generate_token') {
            $api = new MohaaStatsAPIClient();
            $result = $api->initDeviceAuth($user_info['id']);
            $context['mohaa_token'] = $result['user_code'] ?? null;
            $context['mohaa_token_expires'] = $result['expires_in'] ?? 600;
        }
        elseif ($_POST['action_type'] === 'generate_claim') {
            $api = new MohaaStatsAPIClient();
            $result = $api->initClaim($user_info['id']);
            $context['mohaa_claim_code'] = $result['code'] ?? null;
        }
        elseif ($_POST['action_type'] === 'unlink' && !empty($_POST['identity_id'])) {
            MohaaStats_UnlinkIdentity($user_info['id'], (int)$_POST['identity_id']);
            $context['mohaa_identities'] = MohaaStats_GetUserIdentities($user_info['id']);
        }
    }
}

/**
 * Check if a GUID is linked to current user
 */
function MohaaStats_IsLinkedToUser(string $guid): bool
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest'])
        return false;
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
            AND player_guid = {string:guid}',
        [
            'member' => $user_info['id'],
            'guid' => $guid,
        ]
    );
    
    $found = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $found;
}

/**
 * Get all identities linked to a user
 */
function MohaaStats_GetUserIdentities(int $userId): array
{
    global $smcFunc;
    
    $identities = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity, player_guid, player_name, linked_date
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC',
        [
            'member' => $userId,
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $identities[] = $row;
    }
    
    $smcFunc['db_free_result']($request);
    
    return $identities;
}

/**
 * Unlink an identity from a user
 */
function MohaaStats_UnlinkIdentity(int $userId, int $identityId): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}mohaa_identities
        WHERE id_identity = {int:identity}
            AND id_member = {int:member}',
        [
            'identity' => $identityId,
            'member' => $userId,
        ]
    );
}

/**
 * Weapon leaderboard page
 */
function MohaaStats_WeaponLeaderboard(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_weapon_leaderboard'];
    $context['sub_template'] = 'mohaa_stats_weapon_leaderboard';
    
    $weapon = isset($_GET['weapon']) ? $_GET['weapon'] : 'garand';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_weapon'] = $weapon;
    $context['mohaa_weapons_list'] = $api->getWeaponsList();
    $context['mohaa_weapon_data'] = $api->getWeaponStats($weapon);
    $context['mohaa_weapon_leaderboard'] = $api->getWeaponLeaderboard($weapon, 25);
}

/**
 * Map leaderboard page
 */
function MohaaStats_MapLeaderboard(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_map_stats'];
    $context['sub_template'] = 'mohaa_stats_map_leaderboard';
    
    $map = isset($_GET['map']) ? $_GET['map'] : '';
    
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_map'] = $map;
    $context['mohaa_maps_list'] = $api->getMapsList();
    
    if (!empty($map)) {
        $context['mohaa_map_data'] = $api->getMapDetails($map);
        $context['mohaa_map_leaderboard'] = $api->getMapLeaderboard($map, 25);
    } else {
        $context['mohaa_map_data'] = [];
        $context['mohaa_map_leaderboard'] = [];
    }
}

/**
 * Generate token page
 */
function MohaaStats_GenerateToken(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_game_token'];
    $context['sub_template'] = 'mohaa_stats_token';
    
    // Check if identity is linked
    $context['mohaa_identity_linked'] = !empty(MohaaStats_GetUserIdentities($user_info['id']));
}

/**
 * Handle claim code generation
 */
function MohaaStats_HandleClaim(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('post');
    
    $api = new MohaaStatsAPIClient();
    $result = $api->initClaim($user_info['id']);
    
    $context['page_title'] = $txt['mohaa_link_identity'];
    $context['sub_template'] = 'mohaa_stats_link_identity';
    $context['mohaa_identity_linked'] = false;
    $context['mohaa_claim_code'] = $result['code'] ?? null;
    $context['mohaa_claim_expires'] = time() + ($result['expires_in'] ?? 600);
}

/**
 * Handle token generation
 */
function MohaaStats_HandleToken(): void
{
    global $context, $txt, $user_info;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('post');
    
    $api = new MohaaStatsAPIClient();
    $result = $api->initDeviceAuth($user_info['id']);
    
    $context['page_title'] = $txt['mohaa_game_token'];
    $context['sub_template'] = 'mohaa_stats_token';
    $context['mohaa_identity_linked'] = !empty(MohaaStats_GetUserIdentities($user_info['id']));
    $context['mohaa_token'] = $result['user_code'] ?? null;
    $context['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
}

/**
 * Server Dashboard page
 */
function MohaaStats_ServerDashboard(): void
{
    global $context, $txt;
    
    $context['page_title'] = $txt['mohaa_servers'] ?? 'Server Dashboard';
    $context['sub_template'] = 'mohaa_server_stats';
    
    $api = new MohaaStatsAPIClient();
    
    // Fetch parallel
    $requests = [
        'activity' => ['endpoint' => '/stats/global/activity'],
        'maps' => ['endpoint' => '/stats/maps/popularity'],
        'recent_matches' => ['endpoint' => '/stats/matches', 'params' => ['limit' => 10]],
    ];
    
    $results = $api->getMultiple($requests);
    
    $context['mohaa_server_stats'] = [
        'activity' => $results['activity'] ?? [],
        'maps' => $results['maps'] ?? [],
        'recent_matches' => $results['recent_matches'] ?? [],
    ];
}

