<?php
/**
 * MOHAA Login Tokens Plugin
 * 
 * Generate login tokens for in-game authentication via tracker.scr "login" command.
 * Links the SMF user ID with the player's in-game identity.
 *
 * @package MohaaLoginTokens
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

// Token constants
define('MOHAA_TOKEN_LENGTH', 32);
define('MOHAA_TOKEN_EXPIRY', 600); // 10 minutes for one-time login tokens
define('MOHAA_API_TOKEN_EXPIRY', 86400 * 30); // 30 days for API tokens

/**
 * Register actions
 */
function MohaaLoginTokens_Actions(array &$actions): void
{
    // Public action for token generation page
    $actions['mohaatoken'] = ['MohaaLoginTokens.php', 'MohaaLoginTokens_Main'];
    
    // API endpoint for token verification (called by game server)
    $actions['mohaaauth'] = ['MohaaLoginTokens.php', 'MohaaLoginTokens_API'];
}

/**
 * Add profile areas
 */
function MohaaLoginTokens_ProfileAreas(array &$profile_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaatoken'] = [
        'label' => $txt['mohaa_game_login'],
        'file' => 'MohaaLoginTokens.php',
        'function' => 'MohaaLoginTokens_ProfileToken',
        'icon' => 'members',
    ];
}

/**
 * Main dispatcher
 */
function MohaaLoginTokens_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaLoginTokens');
    
    $subActions = [
        'generate' => 'MohaaLoginTokens_Generate',
        'revoke' => 'MohaaLoginTokens_Revoke',
        'sessions' => 'MohaaLoginTokens_Sessions',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'generate';
    
    call_user_func($subActions[$sa]);
}

/**
 * Generate a new login token
 * This is the main page where players get their token to use with /login command
 */
function MohaaLoginTokens_Generate(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_game_login'];
    $context['sub_template'] = 'mohaa_token_generate';
    
    // Check for existing active token
    $existingToken = null;
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_login_tokens
        WHERE id_member = {int:member}
            AND token_type = {string:login}
            AND status = {string:active}
            AND expires_date > {int:now}
        ORDER BY created_date DESC
        LIMIT 1',
        [
            'member' => $user_info['id'],
            'login' => 'login',
            'active' => 'active',
            'now' => time(),
        ]
    );
    
    if ($row = $smcFunc['db_fetch_assoc']($request)) {
        $existingToken = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Generate new token if requested
    $newToken = null;
    if (isset($_POST['generate_token'])) {
        checkSession();
        
        // Expire any existing tokens
        $smcFunc['db_query']('', '
            UPDATE {db_prefix}mohaa_login_tokens
            SET status = {string:expired}
            WHERE id_member = {int:member}
                AND token_type = {string:login}
                AND status = {string:active}',
            [
                'member' => $user_info['id'],
                'login' => 'login',
                'active' => 'active',
                'expired' => 'expired',
            ]
        );
        
        // Generate new token
        $token = MohaaLoginTokens_GenerateSecureToken();
        $now = time();
        $expires = $now + MOHAA_TOKEN_EXPIRY;
        
        $smcFunc['db_insert']('insert',
            '{db_prefix}mohaa_login_tokens',
            [
                'id_member' => 'int',
                'token' => 'string',
                'token_type' => 'string',
                'created_date' => 'int',
                'expires_date' => 'int',
                'status' => 'string',
            ],
            [
                $user_info['id'],
                $token,
                'login',
                $now,
                $expires,
                'active',
            ],
            ['id_token']
        );
        
        // Log token creation
        MohaaLoginTokens_Log($user_info['id'], $token, 'created');
        
        $newToken = [
            'token' => $token,
            'expires_date' => $expires,
        ];
        
        $existingToken = null; // Use new token
    }
    
    // Get recent sessions
    $sessions = [];
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_login_sessions
        WHERE id_member = {int:member}
        ORDER BY login_time DESC
        LIMIT 5',
        ['member' => $user_info['id']]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $sessions[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_token'] = [
        'existing' => $existingToken,
        'new' => $newToken,
        'token_expiry_minutes' => MOHAA_TOKEN_EXPIRY / 60,
        'sessions' => $sessions,
        'member_id' => $user_info['id'],
        'member_name' => $user_info['name'],
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatoken',
        'name' => $txt['mohaa_game_login'],
    ];
}

/**
 * Profile token management page
 */
function MohaaLoginTokens_ProfileToken(int $memID): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    loadTemplate('MohaaLoginTokens');
    
    // Only own profile
    if ($memID != $user_info['id'] && !allowedTo('admin_forum')) {
        fatal_lang_error('cannot_view_token', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_game_login'];
    $context['sub_template'] = 'mohaa_profile_token';
    
    // Get linked identity
    $linkedGUID = null;
    $request = $smcFunc['db_query']('', '
        SELECT player_guid, verified, created_date FROM {db_prefix}mohaa_identities
        WHERE id_member = {int:member}',
        ['member' => $memID]
    );
    
    if ($row = $smcFunc['db_fetch_assoc']($request)) {
        $linkedGUID = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get active API token
    $apiToken = null;
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_login_tokens
        WHERE id_member = {int:member}
            AND token_type = {string:api}
            AND status = {string:active}
            AND expires_date > {int:now}
        LIMIT 1',
        [
            'member' => $memID,
            'api' => 'api',
            'active' => 'active',
            'now' => time(),
        ]
    );
    
    if ($row = $smcFunc['db_fetch_assoc']($request)) {
        $apiToken = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Handle actions
    if ($memID == $user_info['id']) {
        if (isset($_POST['generate_api_token'])) {
            checkSession();
            
            // Revoke existing API tokens
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}mohaa_login_tokens
                SET status = {string:revoked}
                WHERE id_member = {int:member} AND token_type = {string:api} AND status = {string:active}',
                ['member' => $memID, 'api' => 'api', 'active' => 'active', 'revoked' => 'revoked']
            );
            
            // Generate new API token
            $token = MohaaLoginTokens_GenerateSecureToken();
            $smcFunc['db_insert']('insert',
                '{db_prefix}mohaa_login_tokens',
                [
                    'id_member' => 'int',
                    'token' => 'string',
                    'token_type' => 'string',
                    'created_date' => 'int',
                    'expires_date' => 'int',
                    'status' => 'string',
                ],
                [
                    $memID,
                    $token,
                    'api',
                    time(),
                    time() + MOHAA_API_TOKEN_EXPIRY,
                    'active',
                ],
                ['id_token']
            );
            
            MohaaLoginTokens_Log($memID, $token, 'created');
            
            redirectexit('action=profile;area=mohaatoken;u=' . $memID);
        }
        
        if (isset($_POST['revoke_api_token'])) {
            checkSession();
            
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}mohaa_login_tokens
                SET status = {string:revoked}
                WHERE id_member = {int:member} AND token_type = {string:api} AND status = {string:active}',
                ['member' => $memID, 'api' => 'api', 'active' => 'active', 'revoked' => 'revoked']
            );
            
            redirectexit('action=profile;area=mohaatoken;u=' . $memID);
        }
    }
    
    // Get login history
    $loginHistory = [];
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_token_log
        WHERE id_member = {int:member}
        ORDER BY log_time DESC
        LIMIT 10',
        ['member' => $memID]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $loginHistory[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_profile_token'] = [
        'member_id' => $memID,
        'linked_guid' => $linkedGUID,
        'api_token' => $apiToken,
        'login_history' => $loginHistory,
        'is_own' => $memID == $user_info['id'],
    ];
}

/**
 * API endpoint for token verification
 * Called by the game server/tracker to verify a login token
 * 
 * REQUEST: POST /index.php?action=mohaaauth
 * PARAMS: token, guid, server_ip (optional), server_port (optional)
 * RETURNS: JSON { success: bool, member_id: int, member_name: string, error: string }
 */
function MohaaLoginTokens_API(): void
{
    global $smcFunc, $modSettings;
    
    header('Content-Type: application/json');
    
    // Verify API key for server-to-server auth
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_POST['api_key'] ?? '';
    if (empty($modSettings['mohaa_api_key']) || $apiKey !== $modSettings['mohaa_api_key']) {
        echo json_encode(['success' => false, 'error' => 'Invalid API key']);
        exit;
    }
    
    $action = $_POST['action'] ?? $_GET['do'] ?? 'verify';
    
    switch ($action) {
        case 'verify':
            MohaaLoginTokens_APIVerify();
            break;
            
        case 'heartbeat':
            MohaaLoginTokens_APIHeartbeat();
            break;
            
        case 'logout':
            MohaaLoginTokens_APILogout();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    
    exit;
}

/**
 * Verify a login token (API)
 */
function MohaaLoginTokens_APIVerify(): void
{
    global $smcFunc;
    
    $token = $_POST['token'] ?? '';
    $guid = $_POST['guid'] ?? '';
    $serverIP = $_POST['server_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $serverPort = (int)($_POST['server_port'] ?? 0);
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'error' => 'Token required']);
        return;
    }
    
    // Look up token
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name, m.real_name
        FROM {db_prefix}mohaa_login_tokens AS t
        INNER JOIN {db_prefix}members AS m ON t.id_member = m.id_member
        WHERE t.token = {string:token}
            AND t.status = {string:active}
            AND t.expires_date > {int:now}',
        [
            'token' => $token,
            'active' => 'active',
            'now' => time(),
        ]
    );
    
    $tokenRow = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$tokenRow) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
        return;
    }
    
    $memberId = $tokenRow['id_member'];
    $memberName = $tokenRow['real_name'] ?: $tokenRow['member_name'];
    
    // Mark token as used (one-time use for login tokens)
    if ($tokenRow['token_type'] === 'login') {
        $smcFunc['db_query']('', '
            UPDATE {db_prefix}mohaa_login_tokens
            SET status = {string:used}, used_date = {int:now}, used_ip = {string:ip}, player_guid = {string:guid}
            WHERE id_token = {int:id}',
            [
                'id' => $tokenRow['id_token'],
                'used' => 'used',
                'now' => time(),
                'ip' => $serverIP,
                'guid' => $guid,
            ]
        );
        
        MohaaLoginTokens_Log($memberId, $token, 'used', $serverIP, $guid);
    }
    
    // Link GUID to member if not already linked
    if (!empty($guid)) {
        $smcFunc['db_query']('', '
            INSERT INTO {db_prefix}mohaa_identities (id_member, player_guid, verified, created_date)
            VALUES ({int:member}, {string:guid}, 1, {int:now})
            ON DUPLICATE KEY UPDATE verified = 1, last_seen = {int:now}',
            [
                'member' => $memberId,
                'guid' => $guid,
                'now' => time(),
            ]
        );
        
        // Create login session
        $smcFunc['db_query']('', '
            INSERT INTO {db_prefix}mohaa_login_sessions
            (id_member, player_guid, server_ip, server_port, login_time, last_seen, status)
            VALUES ({int:member}, {string:guid}, {string:ip}, {int:port}, {int:now}, {int:now}, {string:active})',
            [
                'member' => $memberId,
                'guid' => $guid,
                'ip' => $serverIP,
                'port' => $serverPort,
                'now' => time(),
                'active' => 'active',
            ]
        );
    }
    
    // Return success with member info (SMF user ID!)
    echo json_encode([
        'success' => true,
        'member_id' => $memberId,      // SMF DATABASE ID - use this in all API calls!
        'member_name' => $memberName,
        'guid' => $guid,
    ]);
}

/**
 * Session heartbeat (API)
 */
function MohaaLoginTokens_APIHeartbeat(): void
{
    global $smcFunc;
    
    $guid = $_POST['guid'] ?? '';
    $memberId = (int)($_POST['member_id'] ?? 0);
    
    if (empty($guid) || empty($memberId)) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        return;
    }
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_login_sessions
        SET last_seen = {int:now}
        WHERE id_member = {int:member} AND player_guid = {string:guid} AND status = {string:active}',
        [
            'member' => $memberId,
            'guid' => $guid,
            'now' => time(),
            'active' => 'active',
        ]
    );
    
    echo json_encode(['success' => true]);
}

/**
 * Logout session (API)
 */
function MohaaLoginTokens_APILogout(): void
{
    global $smcFunc;
    
    $guid = $_POST['guid'] ?? '';
    $memberId = (int)($_POST['member_id'] ?? 0);
    
    if (empty($guid) || empty($memberId)) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        return;
    }
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_login_sessions
        SET status = {string:offline}, logout_time = {int:now}
        WHERE id_member = {int:member} AND player_guid = {string:guid} AND status = {string:active}',
        [
            'member' => $memberId,
            'guid' => $guid,
            'now' => time(),
            'offline' => 'offline',
            'active' => 'active',
        ]
    );
    
    echo json_encode(['success' => true]);
}

/**
 * Generate a cryptographically secure token
 */
function MohaaLoginTokens_GenerateSecureToken(): string
{
    return bin2hex(random_bytes(MOHAA_TOKEN_LENGTH / 2));
}

/**
 * Log token activity
 */
function MohaaLoginTokens_Log(int $memberId, string $token, string $action, string $ip = null, string $guid = null): void
{
    global $smcFunc;
    
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_token_log',
        [
            'id_member' => 'int',
            'token_prefix' => 'string',
            'action' => 'string',
            'ip_address' => 'string',
            'player_guid' => 'string',
            'log_time' => 'int',
        ],
        [
            $memberId,
            substr($token, 0, 8),
            $action,
            $ip ?? $_SERVER['REMOTE_ADDR'] ?? '',
            $guid ?? '',
            time(),
        ],
        ['id_log']
    );
}

/**
 * Cleanup expired tokens (called by cron or scheduled task)
 */
function MohaaLoginTokens_Cleanup(): void
{
    global $smcFunc;
    
    // Expire old tokens
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_login_tokens
        SET status = {string:expired}
        WHERE status = {string:active} AND expires_date < {int:now}',
        ['active' => 'active', 'expired' => 'expired', 'now' => time()]
    );
    
    // Mark stale sessions as offline (no heartbeat for 5 minutes)
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_login_sessions
        SET status = {string:offline}, logout_time = {int:now}
        WHERE status = {string:active} AND last_seen < {int:stale}',
        [
            'active' => 'active',
            'offline' => 'offline',
            'now' => time(),
            'stale' => time() - 300,
        ]
    );
}
