<?php
/**
 * MOHAA Tournaments Plugin
 * 
 * Create, join, and manage tournaments
 *
 * @package MohaaTournaments
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaTournaments_Actions(array &$actions): void
{
    $actions['mohaatournaments'] = ['MohaaTournaments.php', 'MohaaTournaments_Main'];
}

/**
 * Add admin areas
 */
function MohaaTournaments_AdminAreas(array &$admin_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $admin_areas['config']['areas']['mohaatournaments'] = [
        'label' => $txt['mohaa_tournaments_admin'],
        'file' => 'MohaaTournaments.php',
        'function' => 'MohaaTournaments_Admin',
        'icon' => 'calendar',
    ];
}

/**
 * Admin page (Init/Status)
 */
function MohaaTournaments_Admin(): void
{
    global $context, $txt, $smcFunc, $db_prefix;

    isAllowedTo('admin_forum');

    loadLanguage('MohaaStats');
    loadTemplate('MohaaTournaments');

    // Handle actions
    if (!empty($_POST['mohaa_action'])) {
        checkSession();

        if ($_POST['mohaa_action'] === 'seed_demo') {
            MohaaTournaments_SeedDemo();
            $context['mohaa_admin_notice'] = $txt['mohaa_tournament_seeded'];
        }
    }

    // Table status
    $tables = [
        'mohaa_tournaments',
        'mohaa_tournament_registrations',
        'mohaa_tournament_matches',
        'mohaa_tournament_admins',
    ];

    $statuses = [];
    foreach ($tables as $table) {
        $request = $smcFunc['db_query']('', 'SHOW TABLES LIKE {string:table}', ['table' => $db_prefix . $table]);
        $statuses[$table] = $smcFunc['db_num_rows']($request) > 0;
        $smcFunc['db_free_result']($request);
    }

    // Counts
    $count = $smcFunc['db_query']('', 'SELECT COUNT(*) AS total FROM {db_prefix}mohaa_tournaments');
    $row = $smcFunc['db_fetch_assoc']($count);
    $smcFunc['db_free_result']($count);

    $context['page_title'] = $txt['mohaa_tournaments_admin'];
    $context['sub_template'] = 'mohaa_tournaments_admin';
    $context['mohaa_admin'] = [
        'tables' => $statuses,
        'tournament_count' => (int)($row['total'] ?? 0),
    ];
}

/**
 * Main dispatcher
 */
function MohaaTournaments_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaTournaments');
    
    $subActions = [
        'list' => 'MohaaTournaments_List',
        'view' => 'MohaaTournaments_View',
        'create' => 'MohaaTournaments_Create',
        'edit' => 'MohaaTournaments_Edit',
        'register' => 'MohaaTournaments_Register',
        'withdraw' => 'MohaaTournaments_Withdraw',
        'bracket' => 'MohaaTournaments_Bracket',
        'match' => 'MohaaTournaments_Match',
        'start' => 'MohaaTournaments_Start',
        'manage' => 'MohaaTournaments_Manage',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    call_user_func($subActions[$sa]);
}

/**
 * List all tournaments
 */
function MohaaTournaments_List(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_tournaments'];
    $context['sub_template'] = 'mohaa_tournaments_list';
    
    // Fetch tournaments
    $context['mohaa_tournaments'] = [
        'active' => [],
        'upcoming' => [],
        'completed' => [],
    ];
    
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as creator_name,
            (SELECT COUNT(*) FROM {db_prefix}mohaa_tournament_registrations WHERE id_tournament = t.id_tournament AND status = {string:approved}) as participant_count
        FROM {db_prefix}mohaa_tournaments AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_creator = m.id_member
        WHERE t.status != {string:draft}
        ORDER BY 
            CASE t.status 
                WHEN {string:active} THEN 1 
                WHEN {string:registration} THEN 2 
                WHEN {string:completed} THEN 3 
            END,
            t.tournament_start DESC',
        [
            'draft' => 'draft',
            'active' => 'active',
            'registration' => 'registration',
            'completed' => 'completed',
            'approved' => 'approved',
        ]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $category = match($row['status']) {
            'active' => 'active',
            'registration' => 'upcoming',
            default => 'completed',
        };
        $context['mohaa_tournaments'][$category][] = $row;
    }
    
    $smcFunc['db_free_result']($request);
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments',
        'name' => $txt['mohaa_tournaments'],
    ];
}

/**
 * View tournament details
 */
function MohaaTournaments_View(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaatournaments');
        return;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as creator_name
        FROM {db_prefix}mohaa_tournaments AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_creator = m.id_member
        WHERE t.id_tournament = {int:id}',
        ['id' => $id]
    );
    
    $tournament = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$tournament) {
        fatal_lang_error('mohaa_tournament_not_found', false);
        return;
    }
    
    $context['page_title'] = $tournament['name'];
    $context['sub_template'] = 'mohaa_tournament_view';
    
    // Get participants
    $participants = [];
    $request = $smcFunc['db_query']('', '
        SELECT r.*, m.member_name, m.real_name
        FROM {db_prefix}mohaa_tournament_registrations AS r
        LEFT JOIN {db_prefix}members AS m ON r.id_member = m.id_member
        WHERE r.id_tournament = {int:id}
        ORDER BY r.status = {string:approved} DESC, r.registered_date ASC',
        ['id' => $id, 'approved' => 'approved']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $participants[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Check if current user is registered
    $myRegistration = null;
    foreach ($participants as $p) {
        if ($p['id_member'] == $user_info['id']) {
            $myRegistration = $p;
            break;
        }
    }
    
    // Check if user is admin
    $isAdmin = MohaaTournaments_IsAdmin($id, $user_info['id']);
    
    $context['mohaa_tournament'] = [
        'info' => $tournament,
        'participants' => $participants,
        'my_registration' => $myRegistration,
        'is_admin' => $isAdmin,
        'can_register' => $tournament['status'] === 'registration' && !$myRegistration && !$user_info['is_guest'],
        'can_withdraw' => !empty($myRegistration) && $myRegistration['status'] !== 'withdrawn',
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments',
        'name' => $txt['mohaa_tournaments'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments;sa=view;id=' . $id,
        'name' => $tournament['name'],
    ];
}

/**
 * Create a new tournament
 */
function MohaaTournaments_Create(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    // Check permission (could require specific group)
    if (!allowedTo('mohaa_create_tournament') && !allowedTo('admin_forum')) {
        fatal_lang_error('mohaa_not_allowed', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_create_tournament'];
    $context['sub_template'] = 'mohaa_tournament_create';
    
    // Handle form submission
    if (isset($_POST['create_tournament'])) {
        checkSession();
        
        $name = trim($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $type = $_POST['tournament_type'] ?? 'single_elim';
        $teamSize = (int)($_POST['team_size'] ?? 1);
        $maxTeams = (int)($_POST['max_teams'] ?? 16);
        $gameMode = $_POST['game_mode'] ?? 'tdm';
        $maps = $_POST['maps'] ?? '';
        $rules = $_POST['rules'] ?? '';
        $prizeInfo = $_POST['prize_info'] ?? '';
        $regStart = strtotime($_POST['registration_start'] ?? '');
        $regEnd = strtotime($_POST['registration_end'] ?? '');
        $tournamentStart = strtotime($_POST['tournament_start'] ?? '');
        
        if (empty($name)) {
            $context['mohaa_error'] = $txt['mohaa_tournament_name_required'];
        } else {
            // Insert tournament
            $smcFunc['db_insert']('insert',
                '{db_prefix}mohaa_tournaments',
                [
                    'id_creator' => 'int',
                    'name' => 'string',
                    'description' => 'string',
                    'tournament_type' => 'string',
                    'team_size' => 'int',
                    'max_teams' => 'int',
                    'game_mode' => 'string',
                    'maps' => 'string',
                    'rules' => 'string',
                    'prize_info' => 'string',
                    'status' => 'string',
                    'registration_start' => 'int',
                    'registration_end' => 'int',
                    'tournament_start' => 'int',
                    'created_date' => 'int',
                ],
                [
                    $user_info['id'],
                    $name,
                    $description,
                    $type,
                    $teamSize,
                    $maxTeams,
                    $gameMode,
                    $maps,
                    $rules,
                    $prizeInfo,
                    'draft',
                    $regStart ?: 0,
                    $regEnd ?: 0,
                    $tournamentStart ?: 0,
                    time(),
                ],
                ['id_tournament']
            );
            
            $newId = $smcFunc['db_insert_id']('{db_prefix}mohaa_tournaments');
            
            // Add creator as owner
            $smcFunc['db_insert']('insert',
                '{db_prefix}mohaa_tournament_admins',
                ['id_tournament' => 'int', 'id_member' => 'int', 'role' => 'string'],
                [$newId, $user_info['id'], 'owner'],
                []
            );
            
            redirectexit('action=mohaatournaments;sa=manage;id=' . $newId);
        }
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments;sa=create',
        'name' => $txt['mohaa_create_tournament'],
    ];
}

/**
 * Seed demo tournaments (admin action)
 */
function MohaaTournaments_SeedDemo(): void
{
    global $smcFunc, $user_info;

    // Avoid duplicates
    $request = $smcFunc['db_query']('', 'SELECT COUNT(*) AS total FROM {db_prefix}mohaa_tournaments');
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    if (!empty($row['total'])) {
        return;
    }

    $now = time();
    $start = $now + 86400;
    $regEnd = $now + 43200;

    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_tournaments',
        [
            'id_creator' => 'int',
            'name' => 'string',
            'description' => 'string',
            'tournament_type' => 'string',
            'team_size' => 'int',
            'max_teams' => 'int',
            'game_mode' => 'string',
            'maps' => 'string',
            'rules' => 'string',
            'prize_info' => 'string',
            'status' => 'string',
            'registration_start' => 'int',
            'registration_end' => 'int',
            'tournament_start' => 'int',
            'created_date' => 'int',
        ],
        [
            $user_info['id'] ?? 1,
            'OpenMOHAA Winter Cup',
            'Official community tournament. Single elimination, BO3 finals.',
            'single_elim',
            1,
            16,
            'tdm',
            'v2_rocket,stalingrad,omaha_beach',
            'Standard rules. No exploits. Good luck.',
            '1st: $100, 2nd: $50, 3rd: $25',
            'registration',
            $now,
            $regEnd,
            $start,
            $now,
        ],
        ['id_tournament']
    );
}

/**
 * Register for a tournament
 */
function MohaaTournaments_Register(): void
{
    global $context, $txt, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaatournaments');
        return;
    }
    
    // Check tournament is in registration
    $request = $smcFunc['db_query']('', '
        SELECT status, max_teams
        FROM {db_prefix}mohaa_tournaments
        WHERE id_tournament = {int:id}',
        ['id' => $id]
    );
    
    $tournament = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$tournament || $tournament['status'] !== 'registration') {
        fatal_lang_error('mohaa_registration_closed', false);
        return;
    }
    
    // Check not already registered
    $request = $smcFunc['db_query']('', '
        SELECT id_registration
        FROM {db_prefix}mohaa_tournament_registrations
        WHERE id_tournament = {int:id} AND id_member = {int:member}',
        ['id' => $id, 'member' => $user_info['id']]
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        redirectexit('action=mohaatournaments;sa=view;id=' . $id);
        return;
    }
    $smcFunc['db_free_result']($request);
    
    // Get linked GUID
    require_once(SOURCEDIR . '/MohaaPlayers.php');
    $guid = MohaaPlayers_GetLinkedGUID($user_info['id']);
    
    // Register
    $smcFunc['db_insert']('insert',
        '{db_prefix}mohaa_tournament_registrations',
        [
            'id_tournament' => 'int',
            'id_member' => 'int',
            'player_guid' => 'string',
            'status' => 'string',
            'registered_date' => 'int',
        ],
        [
            $id,
            $user_info['id'],
            $guid ?? '',
            'approved', // Auto-approve for now
            time(),
        ],
        ['id_registration']
    );
    
    redirectexit('action=mohaatournaments;sa=view;id=' . $id);
}

/**
 * Withdraw from a tournament
 */
function MohaaTournaments_Withdraw(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_tournament_registrations
        SET status = {string:withdrawn}
        WHERE id_tournament = {int:id} AND id_member = {int:member}',
        ['id' => $id, 'member' => $user_info['id'], 'withdrawn' => 'withdrawn']
    );
    
    redirectexit('action=mohaatournaments;sa=view;id=' . $id);
}

/**
 * View tournament bracket
 */
function MohaaTournaments_Bracket(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaatournaments');
        return;
    }
    
    // Get tournament
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_tournaments WHERE id_tournament = {int:id}',
        ['id' => $id]
    );
    $tournament = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$tournament) {
        fatal_lang_error('mohaa_tournament_not_found', false);
        return;
    }
    
    $context['page_title'] = $tournament['name'] . ' - ' . $txt['mohaa_bracket'];
    $context['sub_template'] = 'mohaa_tournament_bracket';
    
    // Get matches
    $matches = [];
    $request = $smcFunc['db_query']('', '
        SELECT m.*,
            p1.member_name as player1_name, p2.member_name as player2_name
        FROM {db_prefix}mohaa_tournament_matches AS m
        LEFT JOIN {db_prefix}members AS p1 ON m.id_player1 = p1.id_member
        LEFT JOIN {db_prefix}members AS p2 ON m.id_player2 = p2.id_member
        WHERE m.id_tournament = {int:id}
        ORDER BY m.round_number, m.match_number',
        ['id' => $id]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $matches[$row['round_number']][] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_bracket'] = [
        'tournament' => $tournament,
        'matches' => $matches,
        'rounds' => count($matches),
    ];
}

/**
 * Start a tournament (generate bracket)
 */
function MohaaTournaments_Start(): void
{
    global $user_info, $smcFunc, $txt;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!MohaaTournaments_IsAdmin($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_tournament_admin', false);
        return;
    }
    
    // Get participants
    $request = $smcFunc['db_query']('', '
        SELECT id_member, player_guid
        FROM {db_prefix}mohaa_tournament_registrations
        WHERE id_tournament = {int:id} AND status = {string:approved}
        ORDER BY registered_date',
        ['id' => $id, 'approved' => 'approved']
    );
    
    $participants = [];
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $participants[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    if (count($participants) < 2) {
        fatal_lang_error('mohaa_not_enough_participants', false);
        return;
    }
    
    // Shuffle for seeding
    shuffle($participants);
    
    // Generate single elimination bracket
    $numRounds = ceil(log(count($participants), 2));
    $bracketSize = pow(2, $numRounds);
    
    // Pad with byes
    while (count($participants) < $bracketSize) {
        $participants[] = null; // BYE
    }
    
    // Create first round matches
    for ($i = 0; $i < $bracketSize / 2; $i++) {
        $p1 = $participants[$i];
        $p2 = $participants[$bracketSize - 1 - $i];
        
        $smcFunc['db_insert']('insert',
            '{db_prefix}mohaa_tournament_matches',
            [
                'id_tournament' => 'int',
                'round_number' => 'int',
                'match_number' => 'int',
                'id_player1' => 'int',
                'id_player2' => 'int',
                'status' => 'string',
            ],
            [
                $id,
                1,
                $i + 1,
                $p1 ? $p1['id_member'] : 0,
                $p2 ? $p2['id_member'] : 0,
                ($p1 && $p2) ? 'pending' : 'completed', // BYE = auto-complete
            ],
            ['id_match']
        );
        
        // If BYE, set winner
        if (!$p1 || !$p2) {
            $winner = $p1 ? $p1['id_member'] : ($p2 ? $p2['id_member'] : 0);
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}mohaa_tournament_matches
                SET id_winner = {int:winner}
                WHERE id_tournament = {int:id} AND round_number = 1 AND match_number = {int:match}',
                ['id' => $id, 'match' => $i + 1, 'winner' => $winner]
            );
        }
    }
    
    // Create empty slots for subsequent rounds
    $matchesInRound = $bracketSize / 4;
    for ($round = 2; $round <= $numRounds; $round++) {
        for ($m = 1; $m <= $matchesInRound; $m++) {
            $smcFunc['db_insert']('insert',
                '{db_prefix}mohaa_tournament_matches',
                [
                    'id_tournament' => 'int',
                    'round_number' => 'int',
                    'match_number' => 'int',
                    'status' => 'string',
                ],
                [$id, $round, $m, 'pending'],
                ['id_match']
            );
        }
        $matchesInRound /= 2;
    }
    
    // Update tournament status
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_tournaments
        SET status = {string:active}
        WHERE id_tournament = {int:id}',
        ['id' => $id, 'active' => 'active']
    );
    
    redirectexit('action=mohaatournaments;sa=bracket;id=' . $id);
}

/**
 * Manage tournament (admin panel)
 */
function MohaaTournaments_Manage(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!MohaaTournaments_IsAdmin($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_tournament_admin', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_manage_tournament'];
    $context['sub_template'] = 'mohaa_tournament_manage';
    
    // Get tournament
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_tournaments WHERE id_tournament = {int:id}',
        ['id' => $id]
    );
    $tournament = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Handle actions
    if (isset($_POST['action'])) {
        checkSession();
        
        switch ($_POST['action']) {
            case 'open_registration':
                $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_tournaments SET status = {string:registration} WHERE id_tournament = {int:id}',
                    ['id' => $id, 'registration' => 'registration']
                );
                break;
                
            case 'close_registration':
                $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_tournaments SET status = {string:draft} WHERE id_tournament = {int:id}',
                    ['id' => $id, 'draft' => 'draft']
                );
                break;
                
            case 'update_match':
                $matchId = (int)$_POST['match_id'];
                $score1 = (int)$_POST['score1'];
                $score2 = (int)$_POST['score2'];
                $winner = $score1 > $score2 ? 1 : 2;
                
                // Get match info
                $request = $smcFunc['db_query']('', '
                    SELECT * FROM {db_prefix}mohaa_tournament_matches WHERE id_match = {int:id}',
                    ['id' => $matchId]
                );
                $match = $smcFunc['db_fetch_assoc']($request);
                $smcFunc['db_free_result']($request);
                
                $winnerId = $winner == 1 ? $match['id_player1'] : $match['id_player2'];
                
                $smcFunc['db_query']('', '
                    UPDATE {db_prefix}mohaa_tournament_matches
                    SET team1_score = {int:s1}, team2_score = {int:s2}, id_winner = {int:winner}, status = {string:completed}, completed_time = {int:time}
                    WHERE id_match = {int:id}',
                    ['id' => $matchId, 's1' => $score1, 's2' => $score2, 'winner' => $winnerId, 'completed' => 'completed', 'time' => time()]
                );
                
                // Advance winner to next round
                MohaaTournaments_AdvanceWinner($id, $match['round_number'], $match['match_number'], $winnerId);
                break;
        }
        
        redirectexit('action=mohaatournaments;sa=manage;id=' . $id);
    }
    
    // Get participants and matches
    $participants = [];
    $request = $smcFunc['db_query']('', '
        SELECT r.*, m.member_name
        FROM {db_prefix}mohaa_tournament_registrations AS r
        LEFT JOIN {db_prefix}members AS m ON r.id_member = m.id_member
        WHERE r.id_tournament = {int:id}
        ORDER BY r.registered_date',
        ['id' => $id]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $participants[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_manage'] = [
        'tournament' => $tournament,
        'participants' => $participants,
    ];
}

/**
 * Advance winner to next round
 */
function MohaaTournaments_AdvanceWinner(int $tournamentId, int $round, int $matchNum, int $winnerId): void
{
    global $smcFunc;
    
    $nextRound = $round + 1;
    $nextMatch = ceil($matchNum / 2);
    $position = ($matchNum % 2 == 1) ? 1 : 2;
    
    $field = $position == 1 ? 'id_player1' : 'id_player2';
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_tournament_matches
        SET ' . $field . ' = {int:winner}
        WHERE id_tournament = {int:id} AND round_number = {int:round} AND match_number = {int:match}',
        ['id' => $tournamentId, 'round' => $nextRound, 'match' => $nextMatch, 'winner' => $winnerId]
    );
}

/**
 * Register tournament permissions
 */
function MohaaTournaments_Permissions(array &$permissionGroups, array &$permissionList): void
{
    // Add permission group
    $permissionGroups['membergroup']['simple'][] = 'mohaa_tournaments';
    $permissionGroups['membergroup']['classic'][] = 'mohaa_tournaments';
    
    // Register permissions
    $permissionList['membergroup']['mohaa_create_tournament'] = [false, 'mohaa_tournaments', 'mohaa_tournaments'];
    $permissionList['membergroup']['mohaa_join_tournament'] = [true, 'mohaa_tournaments', 'mohaa_tournaments'];
}

/**
 * View/Report individual match
 */
function MohaaTournaments_Match(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $matchId = isset($_GET['match']) ? (int)$_GET['match'] : 0;
    
    if (empty($matchId)) {
        redirectexit('action=mohaatournaments');
        return;
    }
    
    // Get match details
    $request = $smcFunc['db_query']('', '
        SELECT m.*, t.name as tournament_name, t.id_tournament,
               p1.member_name as player1_name, p1.real_name as player1_real,
               p2.member_name as player2_name, p2.real_name as player2_real,
               w.member_name as winner_name
        FROM {db_prefix}mohaa_tournament_matches AS m
        LEFT JOIN {db_prefix}mohaa_tournaments AS t ON m.id_tournament = t.id_tournament
        LEFT JOIN {db_prefix}members AS p1 ON m.id_player1 = p1.id_member
        LEFT JOIN {db_prefix}members AS p2 ON m.id_player2 = p2.id_member
        LEFT JOIN {db_prefix}members AS w ON m.id_winner = w.id_member
        WHERE m.id_match = {int:match_id}',
        ['match_id' => $matchId]
    );
    
    $match = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$match) {
        fatal_lang_error('mohaa_match_not_found', false);
        return;
    }
    
    // Check if user can report scores (is tournament admin or one of the players)
    $canReport = MohaaTournaments_IsAdmin($match['id_tournament'], $user_info['id'])
        || $match['id_player1'] == $user_info['id']
        || $match['id_player2'] == $user_info['id'];
    
    // Handle score submission
    if ($canReport && isset($_POST['report_score']) && $match['status'] === 'pending') {
        checkSession();
        
        $score1 = max(0, (int)$_POST['score1']);
        $score2 = max(0, (int)$_POST['score2']);
        
        if ($score1 !== $score2) { // No ties
            $winnerId = $score1 > $score2 ? $match['id_player1'] : $match['id_player2'];
            
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}mohaa_tournament_matches
                SET team1_score = {int:s1}, team2_score = {int:s2}, id_winner = {int:winner}, status = {string:completed}, completed_time = {int:time}
                WHERE id_match = {int:match_id}',
                [
                    'match_id' => $matchId,
                    's1' => $score1,
                    's2' => $score2,
                    'winner' => $winnerId,
                    'completed' => 'completed',
                    'time' => time(),
                ]
            );
            
            // Advance winner to next round
            MohaaTournaments_AdvanceWinner($match['id_tournament'], $match['round_number'], $match['match_number'], $winnerId);
            
            redirectexit('action=mohaatournaments;sa=bracket;id=' . $match['id_tournament']);
            return;
        } else {
            $context['mohaa_error'] = $txt['mohaa_no_ties'];
        }
    }
    
    $context['page_title'] = $txt['mohaa_match'] . ' - ' . $match['tournament_name'];
    $context['sub_template'] = 'mohaa_tournament_match';
    
    $context['mohaa_match'] = [
        'info' => $match,
        'can_report' => $canReport && $match['status'] === 'pending',
        'player1' => [
            'id' => $match['id_player1'],
            'name' => $match['player1_real'] ?: $match['player1_name'] ?: 'TBD',
        ],
        'player2' => [
            'id' => $match['id_player2'],
            'name' => $match['player2_real'] ?: $match['player2_name'] ?: 'TBD',
        ],
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments',
        'name' => $txt['mohaa_tournaments'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments;sa=bracket;id=' . $match['id_tournament'],
        'name' => $match['tournament_name'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments;sa=match;match=' . $matchId,
        'name' => $txt['mohaa_round'] . ' ' . $match['round_number'] . ' - ' . $txt['mohaa_match'] . ' ' . $match['match_number'],
    ];
}

/**
 * Check if user is tournament admin
 */
function MohaaTournaments_IsAdmin(int $tournamentId, int $memberId): bool
{
    global $smcFunc;
    
    if (allowedTo('admin_forum')) {
        return true;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_tournament_admins
        WHERE id_tournament = {int:tid} AND id_member = {int:mid}',
        ['tid' => $tournamentId, 'mid' => $memberId]
    );
    
    $isAdmin = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $isAdmin;
}
