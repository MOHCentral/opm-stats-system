<?php
/**
 * MOHAA Player Stats Plugin
 * 
 * Provides player statistics pages and profile integration.
 * Uses SMF member ID for identity linking.
 *
 * @package MohaaPlayers
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaPlayers_Actions(array &$actions): void
{
    $actions['mohaaplayer'] = ['MohaaPlayers.php', 'MohaaPlayers_ViewPlayer'];
    $actions['mohaadashboard'] = ['MohaaPlayers.php', 'MohaaPlayers_Dashboard'];
    $actions['mohaacompare'] = ['MohaaPlayers.php', 'MohaaPlayers_Compare'];
    $actions['mohaaidentity'] = ['MohaaPlayers.php', 'MohaaPlayers_IdentityRedirect'];
}

/**
 * Add profile areas for game stats
 */
function MohaaPlayers_ProfileAreas(array &$profile_areas): void
{
    global $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled']))
        return;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaastats'] = [
        'label' => $txt['mohaa_game_stats'],
        'file' => 'MohaaPlayers.php',
        'function' => 'MohaaPlayers_ProfileStats',
        'icon' => 'stats',
        'permission' => [
            'own' => 'profile_view_own',
            'any' => 'profile_view_any',
        ],
    ];
    
    $profile_areas['info']['areas']['mohaaidentity'] = [
        'label' => $txt['mohaa_link_identity'],
        'file' => 'MohaaPlayers.php',
        'function' => 'MohaaPlayers_ProfileIdentity',
        'icon' => 'members',
        'permission' => [
            'own' => 'profile_view_own',
            'any' => [],
        ],
    ];
}

/**
 * View a player's game stats page
 */
function MohaaPlayers_ViewPlayer(): void
{
    global $context, $txt, $scripturl;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    // Get player by GUID or by SMF member ID
    $guid = isset($_GET['guid']) ? $_GET['guid'] : '';
    $memberId = isset($_GET['member']) ? (int)$_GET['member'] : 0;
    
    if (empty($guid) && empty($memberId)) {
        fatal_lang_error('mohaa_player_not_specified', false);
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // If member ID provided, get linked GUID
    if (!empty($memberId) && empty($guid)) {
        $guid = MohaaPlayers_GetLinkedGUID($memberId);
        if (empty($guid)) {
            fatal_lang_error('mohaa_no_linked_identity', false);
            return;
        }
    }
    
    // Fetch player data
    $player = $api->getPlayerStats($guid);
    
    if (empty($player)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    // Get linked SMF member if any
    $linkedMember = MohaaPlayers_GetLinkedMember($guid);
    
    $context['page_title'] = sprintf($txt['mohaa_player_title'], $player['name']);
    $context['sub_template'] = 'mohaa_player_full';
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaastats',
        'name' => $txt['mohaa_stats'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaplayer;guid=' . urlencode($guid),
        'name' => $player['name'],
    ];
    
    $context['mohaa_player'] = [
        'guid' => $guid,
        'name' => $player['name'],
        'stats' => $player,
        'linked_member' => $linkedMember,
        'weapons' => $api->getPlayerWeapons($guid),
        'matches' => $api->getPlayerMatches($guid, 20),
        'achievements' => $api->getPlayerAchievements($guid),
        'performance' => $api->getPlayerPerformance($guid, 30), // Last 30 days
        'maps' => $api->getPlayerMapStats($guid),
    ];
}

/**
 * Player dashboard - shows ALL stats and THEIR stats
 */
function MohaaPlayers_Dashboard(): void
{
    global $context, $txt, $user_info, $scripturl;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaDashboard');
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['page_title'] = $txt['mohaa_my_dashboard'];
    $context['sub_template'] = 'mohaa_dashboard';
    
    // Get linked identity for current user
    $myGuid = MohaaPlayers_GetLinkedGUID($user_info['id']);
    
    // Build batch of requests for parallel fetching
    $requests = [
        'stats' => ['endpoint' => '/stats/global'],
        'leaderboard' => ['endpoint' => '/stats/leaderboard/global', 'params' => ['stat' => 'kills', 'limit' => 10]],
        'recent_matches' => ['endpoint' => '/stats/matches', 'params' => ['limit' => 10]],
    ];
    
    // Fetch all global data in parallel (3 requests in ~1 network round-trip)
    $results = $api->getMultiple($requests);
    
    // Global stats - live matches loaded async via JS for freshness
    $context['mohaa_global'] = [
        'stats' => $results['stats'],
        'leaderboard' => $results['leaderboard'],
        'live_matches' => null,
        'recent_matches' => $results['recent_matches'],
        'top_weapons' => [],
        'active_players' => [],
    ];
    
    // My stats (only if linked)
    if (!empty($myGuid)) {
        $playerRequests = [
            'player' => ['endpoint' => '/stats/player/' . urlencode($myGuid)],
            'weapons' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/weapons'],
            'matches' => ['endpoint' => '/stats/player/' . urlencode($myGuid) . '/matches', 'params' => ['limit' => 10]],
            'achievements' => ['endpoint' => '/achievements/player/' . urlencode($myGuid)],
        ];
        
        $playerResults = $api->getMultiple($playerRequests);
        
        $context['mohaa_my'] = [
            'guid' => $myGuid,
            'player' => $playerResults['player'],
            'rank' => null,
            'weapons' => $playerResults['weapons'],
            'recent_matches' => $playerResults['matches'],
            'achievements' => $playerResults['achievements'],
            'performance' => [],
            'comparisons' => [],
        ];
        
        $context['mohaa_has_identity'] = true;
    } else {
        $context['mohaa_has_identity'] = false;
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaadashboard',
        'name' => $txt['mohaa_my_dashboard'],
    ];
}

/**
 * Compare two players
 */
function MohaaPlayers_Compare(): void
{
    global $context, $txt, $scripturl;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    $guid1 = isset($_GET['p1']) ? $_GET['p1'] : '';
    $guid2 = isset($_GET['p2']) ? $_GET['p2'] : '';
    
    if (empty($guid1) || empty($guid2)) {
        $context['sub_template'] = 'mohaa_compare_select';
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $player1 = $api->getPlayerStats($guid1);
    $player2 = $api->getPlayerStats($guid2);
    
    if (empty($player1) || empty($player2)) {
        fatal_lang_error('mohaa_player_not_found', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_compare_players'];
    $context['sub_template'] = 'mohaa_compare';
    
    $context['mohaa_compare'] = [
        'player1' => [
            'guid' => $guid1,
            'name' => $player1['name'],
            'stats' => $player1,
            'weapons' => $api->getPlayerWeapons($guid1),
        ],
        'player2' => [
            'guid' => $guid2,
            'name' => $player2['name'],
            'stats' => $player2,
            'weapons' => $api->getPlayerWeapons($guid2),
        ],
        'head_to_head' => $api->getHeadToHead($guid1, $guid2),
    ];
}

/**
 * Profile page: Game stats tab
 */
function MohaaPlayers_ProfileStats(int $memID): void
{
    global $context, $txt, $memberContext;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayers');
    
    loadMemberData($memID);
    loadMemberContext($memID);
    
    $context['sub_template'] = 'mohaa_profile_stats';
    
    // Get linked GUID for this member
    $guid = MohaaPlayers_GetLinkedGUID($memID);
    
    if (empty($guid)) {
        $context['mohaa_no_identity'] = true;
        return;
    }
    
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $player = $api->getPlayerStats($guid);
    
    $context['mohaa_player'] = [
        'guid' => $guid,
        'name' => $player['name'] ?? 'Unknown',
        'stats' => $player,
        'rank' => $api->getPlayerRank($guid),
        'weapons' => $api->getPlayerWeapons($guid),
        'recent_matches' => $api->getPlayerMatches($guid, 5),
    ];
}


/**
 * Profile page: Identity linking
 */
function MohaaPlayers_ProfileIdentity(int $memID): void
{
    global $context, $txt, $user_info, $scripturl, $smcFunc, $modSettings;
    
    // Only allow viewing own identity settings
    if ($memID != $user_info['id']) {
        redirectexit('action=profile;area=mohaastats;u=' . $memID);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaIdentity');
    
    $context['sub_template'] = 'mohaaidentity';
    $context['page_title'] = $txt['mohaa_link_identity'] ?? 'Link Game Identity';
    
    // Generate or get existing token for this user
    require_once(__DIR__ . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Handle regenerate token action
    if (isset($_POST['regenerate_token'])) {
        checkSession();  // POST session check
        $result = $api->initDeviceAuth($memID, true);  // Force regenerate
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
        redirectexit('action=profile;area=mohaaidentity');
    } elseif (isset($_GET['regenerate'])) {
        checkSession('get');  // GET session check
        $result = $api->initDeviceAuth($memID, true);  // Force regenerate
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
        redirectexit('action=profile;area=mohaaidentity');
    }
    
    // Get or generate token
    if (empty($_SESSION['mohaa_token']) || ($_SESSION['mohaa_token_expires'] ?? 0) < time()) {
        // Generate a new token
        $result = $api->initDeviceAuth($memID);
        if (!empty($result['user_code'])) {
            $_SESSION['mohaa_token'] = $result['user_code'];
            $_SESSION['mohaa_token_expires'] = time() + ($result['expires_in'] ?? 600);
        }
    }
    
    // Set context for template
    $context['mohaa_token'] = $_SESSION['mohaa_token'] ?? 'ERROR - Could not generate token';
    $context['mohaa_console_command'] = '/login ' . ($context['mohaa_token'] ?? '');
    
    // Get login history from database
    $context['mohaa_login_history'] = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT login_date, server_name, ip_address, location, success
        FROM {db_prefix}mohaa_login_history
        WHERE id_member = {int:member}
        ORDER BY login_date DESC
        LIMIT 20',
        [
            'member' => $memID,
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $context['mohaa_login_history'][] = [
            'date' => $row['login_date'],
            'server' => $row['server_name'],
            'ip' => $row['ip_address'],
            'location' => $row['location'] ?? 'Unknown',
            'status' => $row['success'] ? 'Success' : 'Failed',
        ];
    }
    $smcFunc['db_free_result']($request);
    
    // If no history, show placeholder
    if (empty($context['mohaa_login_history'])) {
        $context['mohaa_login_history'] = [
            [
                'date' => time(),
                'server' => 'No login history yet',
                'ip' => '-',
                'location' => '-',
                'status' => '-',
            ],
        ];
    }
}

function MohaaPlayers_GetLinkedGUID(int $memberId): ?string
{
    global $smcFunc;
    
    $request = $smcFunc['db_query']('', '
        SELECT player_guid
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC
        LIMIT 1',
        [
            'member' => $memberId,
        ]
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    return $row ? $row['player_guid'] : null;
}

/**
 * Get SMF member linked to a GUID
 */
function MohaaPlayers_GetLinkedMember(string $guid): ?array
{
    global $smcFunc;
    
    $request = $smcFunc['db_query']('', '
        SELECT i.id_member, m.member_name, m.real_name, m.avatar
        FROM {db_prefix}mohaa_identities AS i
        LEFT JOIN {db_prefix}members AS m ON i.id_member = m.id_member
        WHERE i.player_guid = {string:guid}',
        [
            'guid' => $guid,
        ]
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    return $row ?: null;
}

/**
 * Get all identities for a member
 */
function MohaaPlayers_GetAllIdentities(int $memberId): array
{
    global $smcFunc;
    
    $identities = [];
    
    $request = $smcFunc['db_query']('', '
        SELECT id_identity, player_guid, player_name, linked_date
        FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}
        ORDER BY linked_date DESC',
        [
            'member' => $memberId,
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $identities[] = $row;
    }
    
    $smcFunc['db_free_result']($request);
    
    return $identities;
}

/**
 * Unlink an identity
 */
function MohaaPlayers_UnlinkIdentity(int $memberId, int $identityId): void
{
    global $smcFunc;
    
    $smcFunc['db_query']('', '
        DELETE FROM {db_prefix}mohaa_identities
        WHERE id_identity = {int:identity}
            AND id_member = {int:member}',
        [
            'identity' => $identityId,
            'member' => $memberId,
        ]
    );
}

/**
 * Link an identity to a member (called by API when claim is verified)
 */
function MohaaPlayers_LinkIdentity(int $memberId, string $guid, string $playerName): int
{
    global $smcFunc;
    
    // Check if already linked
    $request = $smcFunc['db_query']('', '
        SELECT id_identity
        FROM {db_prefix}mohaa_identities
        WHERE player_guid = {string:guid}',
        [
            'guid' => $guid,
        ]
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        return 0; // Already linked
    }
    $smcFunc['db_free_result']($request);
    
    // Insert new identity
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_identities',
        [
            'id_member' => 'int',
            'player_guid' => 'string',
            'player_name' => 'string',
            'linked_date' => 'int',
        ],
        [
            $memberId,
            $guid,
            $playerName,
            time(),
        ],
        ['id_identity']
    );
    
    return $smcFunc['db_insert_id']('{db_prefix}mohaa_identities');
}
function MohaaPlayers_MenuButtons(array &$buttons): void {}

/**
 * Redirect ?action=mohaaidentity to the profile area
 */
function MohaaPlayers_IdentityRedirect(): void
{
    global $user_info;
    
    // Must be logged in
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    // Redirect to the profile identity area
    redirectexit('action=profile;area=mohaaidentity');
}
