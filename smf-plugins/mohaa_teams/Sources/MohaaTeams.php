<?php
/**
 * MOHAA Teams Plugin
 * 
 * Create, join, and manage teams
 *
 * @package MohaaTeams
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaTeams_Actions(array &$actions): void
{
    $actions['mohaateams'] = ['MohaaTeams.php', 'MohaaTeams_Main'];
}

/**
 * Add profile areas
 */
function MohaaTeams_ProfileAreas(array &$profile_areas): void
{
    global $txt;
    
    loadLanguage('MohaaStats');
    
    $profile_areas['info']['areas']['mohaateams'] = [
        'label' => $txt['mohaa_my_teams'],
        'file' => 'MohaaTeams.php',
        'function' => 'MohaaTeams_ProfileTeams',
        'icon' => 'members',
    ];
}

/**
 * Main dispatcher
 */
function MohaaTeams_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaTeams');
    
    $subActions = [
        'list' => 'MohaaTeams_List',
        'view' => 'MohaaTeams_View',
        'create' => 'MohaaTeams_Create',
        'edit' => 'MohaaTeams_Edit',
        'join' => 'MohaaTeams_Join',
        'leave' => 'MohaaTeams_Leave',
        'invite' => 'MohaaTeams_Invite',
        'accept' => 'MohaaTeams_AcceptInvite',
        'decline' => 'MohaaTeams_DeclineInvite',
        'manage' => 'MohaaTeams_Manage',
        'rankings' => 'MohaaTeams_Rankings',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    call_user_func($subActions[$sa]);
}

/**
 * List all teams
 */
function MohaaTeams_List(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_teams'];
    $context['sub_template'] = 'mohaa_teams_list';
    
    // Fetch teams
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name,
            (SELECT COUNT(*) FROM {db_prefix}mohaa_team_members WHERE id_team = t.id_team AND status = {string:active}) as member_count
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.status = {string:active}
        ORDER BY t.rating DESC, t.wins DESC',
        ['active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_teams'] = $teams;
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams',
        'name' => $txt['mohaa_teams'],
    ];
}

/**
 * View team details
 */
function MohaaTeams_View(): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaateams');
        return;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.id_team = {int:id}',
        ['id' => $id]
    );
    
    $team = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$team) {
        fatal_lang_error('mohaa_team_not_found', false);
        return;
    }
    
    $context['page_title'] = $team['team_name'];
    $context['sub_template'] = 'mohaa_team_view';
    
    // Get members
    $members = [];
    $request = $smcFunc['db_query']('', '
        SELECT tm.*, m.member_name, m.real_name, m.avatar
        FROM {db_prefix}mohaa_team_members AS tm
        LEFT JOIN {db_prefix}members AS m ON tm.id_member = m.id_member
        WHERE tm.id_team = {int:id} AND tm.status = {string:active}
        ORDER BY FIELD(tm.role, {string:captain}, {string:officer}, {string:member}, {string:substitute})',
        ['id' => $id, 'active' => 'active', 'captain' => 'captain', 'officer' => 'officer', 'member' => 'member', 'substitute' => 'substitute']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $members[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get match history
    $matches = [];
    $request = $smcFunc['db_query']('', '
        SELECT m.*, t.team_name as opponent_team_name
        FROM {db_prefix}mohaa_team_matches AS m
        LEFT JOIN {db_prefix}mohaa_teams AS t ON m.id_opponent = t.id_team
        WHERE m.id_team = {int:id}
        ORDER BY m.match_date DESC
        LIMIT 10',
        ['id' => $id]
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $matches[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Check if current user is a member
    $myMembership = null;
    foreach ($members as $m) {
        if ($m['id_member'] == $user_info['id']) {
            $myMembership = $m;
            break;
        }
    }
    
    // Check for pending invite/request
    $hasPendingInvite = false;
    if (!$myMembership && !$user_info['is_guest']) {
        $request = $smcFunc['db_query']('', '
            SELECT id_invite FROM {db_prefix}mohaa_team_invites
            WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:pending}',
            ['id' => $id, 'member' => $user_info['id'], 'pending' => 'pending']
        );
        $hasPendingInvite = $smcFunc['db_num_rows']($request) > 0;
        $smcFunc['db_free_result']($request);
    }
    
    $context['mohaa_team'] = [
        'info' => $team,
        'members' => $members,
        'matches' => $matches,
        'my_membership' => $myMembership,
        'has_pending' => $hasPendingInvite,
        'is_captain' => $myMembership && $myMembership['role'] === 'captain',
        'is_officer' => $myMembership && in_array($myMembership['role'], ['captain', 'officer']),
        'can_join' => !$myMembership && !$hasPendingInvite && !$user_info['is_guest'],
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams',
        'name' => $txt['mohaa_teams'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams;sa=view;id=' . $id,
        'name' => $team['team_name'],
    ];
}

/**
 * Create a new team
 */
function MohaaTeams_Create(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $context['page_title'] = $txt['mohaa_create_team'];
    $context['sub_template'] = 'mohaa_team_create';
    
    // Handle form submission
    if (isset($_POST['create_team'])) {
        checkSession();
        
        $name = trim($_POST['team_name'] ?? '');
        $tag = trim($_POST['team_tag'] ?? '');
        $description = $_POST['description'] ?? '';
        $logo = filter_var($_POST['logo_url'] ?? '', FILTER_VALIDATE_URL) ?: '';
        
        if (empty($name)) {
            $context['mohaa_error'] = $txt['mohaa_team_name_required'];
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $context['mohaa_error'] = $txt['mohaa_team_name_length'];
        } else {
            // Check name uniqueness
            $request = $smcFunc['db_query']('', '
                SELECT id_team FROM {db_prefix}mohaa_teams WHERE team_name = {string:name}',
                ['name' => $name]
            );
            
            if ($smcFunc['db_num_rows']($request) > 0) {
                $smcFunc['db_free_result']($request);
                $context['mohaa_error'] = $txt['mohaa_team_name_taken'];
            } else {
                $smcFunc['db_free_result']($request);
                
                // Insert team
                $smcFunc['db_insert']('insert',
                    '{db_prefix}mohaa_teams',
                    [
                        'team_name' => 'string',
                        'team_tag' => 'string',
                        'description' => 'string',
                        'logo_url' => 'string',
                        'id_captain' => 'int',
                        'founded_date' => 'int',
                        'status' => 'string',
                        'rating' => 'int',
                    ],
                    [
                        $name,
                        $tag,
                        $description,
                        $logo,
                        $user_info['id'],
                        time(),
                        'active',
                        1000,
                    ],
                    ['id_team']
                );
                
                $newId = $smcFunc['db_insert_id']('{db_prefix}mohaa_teams');
                
                // Add creator as captain
                $smcFunc['db_insert']('insert',
                    '{db_prefix}mohaa_team_members',
                    ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
                    [$newId, $user_info['id'], 'captain', time(), 'active'],
                    []
                );
                
                redirectexit('action=mohaateams;sa=view;id=' . $newId);
            }
        }
    }
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaateams;sa=create',
        'name' => $txt['mohaa_create_team'],
    ];
}

/**
 * Request to join a team
 */
function MohaaTeams_Join(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($id)) {
        redirectexit('action=mohaateams');
        return;
    }
    
    // Check not already member or pending
    $request = $smcFunc['db_query']('', '
        SELECT id_member FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:active}',
        ['id' => $id, 'member' => $user_info['id'], 'active' => 'active']
    );
    
    if ($smcFunc['db_num_rows']($request) > 0) {
        $smcFunc['db_free_result']($request);
        redirectexit('action=mohaateams;sa=view;id=' . $id);
        return;
    }
    $smcFunc['db_free_result']($request);
    
    // Create join request
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_team_invites',
        [
            'id_team' => 'int',
            'id_member' => 'int',
            'id_inviter' => 'int',
            'invite_type' => 'string',
            'status' => 'string',
            'created_date' => 'int',
        ],
        [
            $id,
            $user_info['id'],
            $user_info['id'],
            'request',
            'pending',
            time(),
        ],
        ['id_invite']
    );
    
    redirectexit('action=mohaateams;sa=view;id=' . $id);
}

/**
 * Leave a team
 */
function MohaaTeams_Leave(): void
{
    global $user_info, $smcFunc, $txt;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Check if captain (can't leave if captain)
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:id} AND id_member = {int:member} AND status = {string:active}',
        ['id' => $id, 'member' => $user_info['id'], 'active' => 'active']
    );
    
    $row = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if ($row && $row['role'] === 'captain') {
        fatal_lang_error('mohaa_captain_cannot_leave', false);
        return;
    }
    
    // Update status
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_members
        SET status = {string:left}
        WHERE id_team = {int:id} AND id_member = {int:member}',
        ['id' => $id, 'member' => $user_info['id'], 'left' => 'left']
    );
    
    redirectexit('action=mohaateams;sa=view;id=' . $id);
}

/**
 * Invite a player to team
 */
function MohaaTeams_Invite(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Check permissions
    if (!MohaaTeams_IsOfficer($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_team_officer', false);
        return;
    }
    
    if (isset($_POST['invite_player'])) {
        checkSession();
        
        $playerId = (int)$_POST['player_id'];
        
        if ($playerId > 0) {
            $smcFunc['db_insert']('replace',
                '{db_prefix}mohaa_team_invites',
                [
                    'id_team' => 'int',
                    'id_member' => 'int',
                    'id_inviter' => 'int',
                    'invite_type' => 'string',
                    'status' => 'string',
                    'created_date' => 'int',
                ],
                [
                    $id,
                    $playerId,
                    $user_info['id'],
                    'invite',
                    'pending',
                    time(),
                ],
                ['id_invite']
            );
        }
        
        redirectexit('action=mohaateams;sa=manage;id=' . $id);
    }
}

/**
 * Accept a team invite
 */
function MohaaTeams_AcceptInvite(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $inviteId = isset($_GET['invite']) ? (int)$_GET['invite'] : 0;
    
    // Get invite
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_team_invites
        WHERE id_invite = {int:id} AND id_member = {int:member} AND status = {string:pending}',
        ['id' => $inviteId, 'member' => $user_info['id'], 'pending' => 'pending']
    );
    
    $invite = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if (!$invite) {
        fatal_lang_error('mohaa_invite_not_found', false);
        return;
    }
    
    // Update invite status
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_invites SET status = {string:accepted} WHERE id_invite = {int:id}',
        ['id' => $inviteId, 'accepted' => 'accepted']
    );
    
    // Add to team
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_team_members',
        ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
        [$invite['id_team'], $user_info['id'], 'member', time(), 'active'],
        []
    );
    
    redirectexit('action=mohaateams;sa=view;id=' . $invite['id_team']);
}

/**
 * Decline a team invite
 */
function MohaaTeams_DeclineInvite(): void
{
    global $user_info, $smcFunc;
    
    if ($user_info['is_guest']) {
        redirectexit('action=login');
        return;
    }
    
    checkSession('get');
    
    $inviteId = isset($_GET['invite']) ? (int)$_GET['invite'] : 0;
    
    $smcFunc['db_query']('', '
        UPDATE {db_prefix}mohaa_team_invites
        SET status = {string:declined}
        WHERE id_invite = {int:id} AND id_member = {int:member}',
        ['id' => $inviteId, 'member' => $user_info['id'], 'declined' => 'declined']
    );
    
    redirectexit('action=profile;area=mohaateams');
}

/**
 * Manage team
 */
function MohaaTeams_Manage(): void
{
    global $context, $txt, $scripturl, $user_info, $smcFunc;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!MohaaTeams_IsOfficer($id, $user_info['id'])) {
        fatal_lang_error('mohaa_not_team_officer', false);
        return;
    }
    
    $context['page_title'] = $txt['mohaa_manage_team'];
    $context['sub_template'] = 'mohaa_team_manage';
    
    // Handle actions
    if (isset($_POST['action'])) {
        checkSession();
        
        switch ($_POST['action']) {
            case 'kick':
                $memberId = (int)$_POST['member_id'];
                if ($memberId > 0 && $memberId != $user_info['id']) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_members
                        SET status = {string:kicked}
                        WHERE id_team = {int:team} AND id_member = {int:member}',
                        ['team' => $id, 'member' => $memberId, 'kicked' => 'kicked']
                    );
                }
                break;
                
            case 'promote':
                $memberId = (int)$_POST['member_id'];
                $role = $_POST['role'] ?? 'member';
                if (in_array($role, ['officer', 'member', 'substitute'])) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_members
                        SET role = {string:role}
                        WHERE id_team = {int:team} AND id_member = {int:member}',
                        ['team' => $id, 'member' => $memberId, 'role' => $role]
                    );
                }
                break;
                
            case 'approve_request':
                $inviteId = (int)$_POST['invite_id'];
                $request = $smcFunc['db_query']('', '
                    SELECT * FROM {db_prefix}mohaa_team_invites WHERE id_invite = {int:id} AND status = {string:pending}',
                    ['id' => $inviteId, 'pending' => 'pending']
                );
                $invite = $smcFunc['db_fetch_assoc']($request);
                $smcFunc['db_free_result']($request);
                
                if ($invite) {
                    $smcFunc['db_query']('', '
                        UPDATE {db_prefix}mohaa_team_invites SET status = {string:accepted} WHERE id_invite = {int:id}',
                        ['id' => $inviteId, 'accepted' => 'accepted']
                    );
                    
                    $smcFunc['db_insert']('replace',
                        '{db_prefix}mohaa_team_members',
                        ['id_team' => 'int', 'id_member' => 'int', 'role' => 'string', 'joined_date' => 'int', 'status' => 'string'],
                        [$invite['id_team'], $invite['id_member'], 'member', time(), 'active'],
                        []
                    );
                }
                break;
        }
        
        redirectexit('action=mohaateams;sa=manage;id=' . $id);
    }
    
    // Get team info
    $request = $smcFunc['db_query']('', '
        SELECT * FROM {db_prefix}mohaa_teams WHERE id_team = {int:id}',
        ['id' => $id]
    );
    $team = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    // Get members
    $members = [];
    $request = $smcFunc['db_query']('', '
        SELECT tm.*, m.member_name
        FROM {db_prefix}mohaa_team_members AS tm
        LEFT JOIN {db_prefix}members AS m ON tm.id_member = m.id_member
        WHERE tm.id_team = {int:id} AND tm.status = {string:active}',
        ['id' => $id, 'active' => 'active']
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $members[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get pending requests
    $requests = [];
    $request = $smcFunc['db_query']('', '
        SELECT i.*, m.member_name
        FROM {db_prefix}mohaa_team_invites AS i
        LEFT JOIN {db_prefix}members AS m ON i.id_member = m.id_member
        WHERE i.id_team = {int:id} AND i.invite_type = {string:request} AND i.status = {string:pending}',
        ['id' => $id, 'request' => 'request', 'pending' => 'pending']
    );
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $requests[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_team_manage'] = [
        'team' => $team,
        'members' => $members,
        'requests' => $requests,
    ];
}

/**
 * Team rankings
 */
function MohaaTeams_Rankings(): void
{
    global $context, $txt, $scripturl, $smcFunc;
    
    $context['page_title'] = $txt['mohaa_team_rankings'];
    $context['sub_template'] = 'mohaa_team_rankings';
    
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, m.member_name as captain_name,
            (SELECT COUNT(*) FROM {db_prefix}mohaa_team_members WHERE id_team = t.id_team AND status = {string:active}) as member_count
        FROM {db_prefix}mohaa_teams AS t
        LEFT JOIN {db_prefix}members AS m ON t.id_captain = m.id_member
        WHERE t.status = {string:active}
        ORDER BY t.rating DESC, t.wins DESC
        LIMIT 100',
        ['active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    $context['mohaa_teams_ranking'] = $teams;
}

/**
 * Profile teams tab
 */
function MohaaTeams_ProfileTeams(int $memID): void
{
    global $context, $txt, $scripturl, $smcFunc, $user_info;
    
    loadTemplate('MohaaTeams');
    
    $context['page_title'] = $txt['mohaa_my_teams'];
    $context['sub_template'] = 'mohaa_profile_teams';
    
    // Get user's teams
    $teams = [];
    $request = $smcFunc['db_query']('', '
        SELECT t.*, tm.role, tm.joined_date
        FROM {db_prefix}mohaa_team_members AS tm
        INNER JOIN {db_prefix}mohaa_teams AS t ON tm.id_team = t.id_team
        WHERE tm.id_member = {int:member} AND tm.status = {string:active}',
        ['member' => $memID, 'active' => 'active']
    );
    
    while ($row = $smcFunc['db_fetch_assoc']($request)) {
        $teams[] = $row;
    }
    $smcFunc['db_free_result']($request);
    
    // Get pending invites (only for own profile)
    $invites = [];
    if ($memID == $user_info['id']) {
        $request = $smcFunc['db_query']('', '
            SELECT i.*, t.team_name, m.member_name as inviter_name
            FROM {db_prefix}mohaa_team_invites AS i
            INNER JOIN {db_prefix}mohaa_teams AS t ON i.id_team = t.id_team
            LEFT JOIN {db_prefix}members AS m ON i.id_inviter = m.id_member
            WHERE i.id_member = {int:member} AND i.invite_type = {string:invite} AND i.status = {string:pending}',
            ['member' => $memID, 'invite' => 'invite', 'pending' => 'pending']
        );
        
        while ($row = $smcFunc['db_fetch_assoc']($request)) {
            $invites[] = $row;
        }
        $smcFunc['db_free_result']($request);
    }
    
    $context['mohaa_profile_teams'] = [
        'teams' => $teams,
        'invites' => $invites,
        'is_own' => $memID == $user_info['id'],
    ];
}

/**
 * Check if user is team officer
 */
function MohaaTeams_IsOfficer(int $teamId, int $memberId): bool
{
    global $smcFunc;
    
    if (allowedTo('admin_forum')) {
        return true;
    }
    
    $request = $smcFunc['db_query']('', '
        SELECT role FROM {db_prefix}mohaa_team_members
        WHERE id_team = {int:team} AND id_member = {int:member} AND status = {string:active}
        AND role IN ({string:captain}, {string:officer})',
        ['team' => $teamId, 'member' => $memberId, 'active' => 'active', 'captain' => 'captain', 'officer' => 'officer']
    );
    
    $isOfficer = $smcFunc['db_num_rows']($request) > 0;
    $smcFunc['db_free_result']($request);
    
    return $isOfficer;
}
