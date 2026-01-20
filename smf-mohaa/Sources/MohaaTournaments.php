<?php
/**
 * MOHAA Tournaments Plugin
 * 
 * Create, viewing and managing tournaments
 *
 * @package MohaaTournaments
 * @version 1.0.0
 */

// if (!defined('SMF'))
//    die('No direct access...');

/**
 * Register actions
 */
function MohaaTournaments_Actions(array &$actions): void
{
    $actions['mohaatournaments'] = ['MohaaTournaments.php', 'MohaaTournaments_Main'];
}

/**
 * Main dispatcher
 */
function MohaaTournaments_Main(): void
{
    global $context, $txt, $modSettings, $smcFunc, $db_prefix;
    
    // Load language and templates
    loadLanguage('MohaaStats');
    loadTemplate('MohaaTournaments');
    
    $subActions = [
        'list' => 'MohaaTournaments_List',
        'view' => 'MohaaTournaments_View',
        'create' => 'MohaaTournaments_Create',
        'register' => 'MohaaTournaments_Register',
        'match' => 'MohaaTournaments_MatchView',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    // Auto-install tables if missing (Lazy Initialization)
    // Robust check: Run installer if ANY of the tables are missing
    $required_tables = ['mohaa_tournaments', 'mohaa_tournament_registrations', 'mohaa_tournament_matches'];
    $needs_install = false;
    
    foreach ($required_tables as $tb) {
        $check = $smcFunc['db_query']('', 'SHOW TABLES LIKE {string:table}', ['table' => $db_prefix . $tb]);
        if ($smcFunc['db_num_rows']($check) == 0) {
            $needs_install = true;
            $smcFunc['db_free_result']($check);
            break;
        }
        $smcFunc['db_free_result']($check);
    }

    if ($needs_install) {
        MohaaTournaments_Install();
    }
    
    call_user_func($subActions[$sa]);
}

/**
 * Auto-install tables
 */
function MohaaTournaments_Install(): void
{
    global $smcFunc, $db_prefix;

    // Direct SQL creation to ensure stability
    $queries = [];

    // 1. Tournaments Table
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournaments` (
      `id_tournament` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text,
      `status` varchar(20) NOT NULL DEFAULT 'open',
      `format` varchar(20) NOT NULL DEFAULT 'single_elim',
      `tournament_start` int(10) unsigned NOT NULL DEFAULT 0,
      `max_teams` int(10) unsigned NOT NULL DEFAULT 16,
      `id_winner_team` int(10) unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY (`id_tournament`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // 2. Tournament Registrations (was Participants)
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournament_registrations` (
      `id_tournament` int(10) unsigned NOT NULL,
      `id_team` int(10) unsigned NOT NULL,
      `seed` int(10) unsigned NOT NULL DEFAULT 0,
      `registration_date` int(10) unsigned NOT NULL DEFAULT 0,
      `status` varchar(20) NOT NULL DEFAULT 'pending',
      PRIMARY KEY (`id_tournament`,`id_team`),
      KEY `id_team` (`id_team`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // 3. Matches
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$db_prefix}mohaa_tournament_matches` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    foreach ($queries as $sql) {
        $smcFunc['db_query']('', $sql, []);
    }
}

/**
 * List all tournaments
 */
function MohaaTournaments_List(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $context['page_title'] = 'Active Tournaments';
    $context['sub_template'] = 'mohaa_tournaments_list';
    
    // Get tournaments
    $tournaments = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, 
            (SELECT COUNT(*) FROM {db_prefix}mohaa_tournament_registrations WHERE id_tournament = t.id_tournament) as team_count
        FROM {db_prefix}mohaa_tournaments AS t
        ORDER BY FIELD(t.status, {string:active}, {string:open}, {string:completed}, {string:archived}), t.tournament_start DESC',
        [
            'active' => 'active', 
            'open' => 'open', 
            'completed' => 'completed', 
            'archived' => 'archived'
        ]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $tournaments[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_tournaments'] = $tournaments;
    $context['can_create_tournament'] = $user_info['is_admin']; // Admin only for now
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments',
        'name' => 'Tournaments',
    ];
}

/**
 * View Tournament Details & Bracket
 */
function MohaaTournaments_View(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (empty($id)) { redirectexit('action=mohaatournaments'); }
    
    // Get Tournament Info
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_tournaments WHERE id_tournament = {int:id}',
        ['id' => $id]
    );
    $tournament = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$tournament) { fatal_error('Tournament not found', false); }
    
    $context['page_title'] = $tournament['name'];
    $context['sub_template'] = 'mohaa_tournament_view';
    
    // Get Participants (Registrations)
    $participants = [];
    $request = $smcFunc['db_query']('', '
        SELECT p.*, t.team_name, t.logo_url
        FROM {db_prefix}mohaa_tournament_registrations AS p
        JOIN {db_prefix}mohaa_teams AS t ON t.id_team = p.id_team
        WHERE p.id_tournament = {int:id}
        ORDER BY p.seed ASC',
        ['id' => $id]
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $participants[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Determine user's eligibility to register a team
    $context['can_register'] = false;
    $context['my_team_id'] = 0;
    
    if (!$user_info['is_guest'] && $tournament['status'] == 'open') {
        // Find if user is a captain of any team
        $request = $smcFunc['db_query']('', '
            SELECT id_team FROM {db_prefix}mohaa_team_members 
            WHERE id_member = {int:member} AND role = {string:captain} AND status = {string:active} LIMIT 1',
            ['member' => $user_info['id'], 'captain' => 'captain', 'active' => 'active']
        );
        $row = $smcFunc['db_fetch_assoc']($request);
        $smcFunc['db_free_result']($request);
        
        if ($row) {
            $context['my_team_id'] = $row['id_team'];
            
            // Check if already registered
            $isRegistered = false;
            foreach ($participants as $p) {
                if ($p['id_team'] == $context['my_team_id']) {
                    $isRegistered = true;
                    break;
                }
            }
            
            if (!$isRegistered && count($participants) < $tournament['max_teams']) {
                $context['can_register'] = true;
            }
        }
    }
    
    $context['mohaa_tournament'] = [
        'info' => $tournament,
        'participants' => $participants,
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments',
        'name' => 'Tournaments',
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaatournaments;sa=view;id=' . $id,
        'name' => $tournament['name'],
    ];
}

/**
 * Register a team
 */
function MohaaTournaments_Register(): void
{
    global $user_info, $smcFunc, $context;
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $teamId = isset($_GET['team']) ? (int)$_GET['team'] : 0;
    
    // Verify user is captain of this team
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_team_members 
        WHERE id_team = {int:team} AND id_member = {int:member} AND status = {string:active}',
        ['team' => $teamId, 'member' => $user_info['id'], 'active' => 'active']
    );
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$row || $row['role'] !== 'captain') {
        fatal_error('Only team captains can register teams.', false);
    }
    
    // Register
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_tournament_registrations',
        ['id_tournament' => 'int', 'id_team' => 'int', 'registration_date' => 'int', 'status' => 'string'],
        [$id, $teamId, time(), 'approved'],
        []
    );
    
    redirectexit('action=mohaatournaments;sa=view;id=' . $id);
}

/**
 * Admin: Create Tournament
 */
function MohaaTournaments_Create(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if (!$user_info['is_admin']) { redirectexit('action=mohaatournaments'); }
    
    if (isset($_POST['save'])) {
        checkSession();
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $teams = (int)$_POST['max_teams'];
        $format = $_POST['format'];
        
        $smcFunc['db_insert']('insert',
            '{db_prefix}mohaa_tournaments',
            ['name' => 'string', 'description' => 'string', 'max_teams' => 'int', 'format' => 'string', 'tournament_start' => 'int'],
            [$name, $desc, $teams, $format, time()],
            ['id_tournament']
        );
        
        redirectexit('action=mohaatournaments');
    }
    
    $context['page_title'] = 'Create Tournament';
    $context['sub_template'] = 'mohaa_tournament_create';
}
