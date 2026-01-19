<?php
/**
 * MOHAA Teams Templates
 *
 * @package MohaaTeams
 * @version 1.0.0
 */

/**
 * Team list template
 */
function template_mohaa_teams_list()
{
    global $context, $txt, $scripturl, $user_info;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_teams'], '</h3>
    </div>
    
    <div class="windowbg" style="display: flex; justify-content: space-between; align-items: center;">
        <p>', $txt['mohaa_teams_intro'], '</p>';

    if (!$user_info['is_guest']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=create" class="button">', $txt['mohaa_create_team'], '</a>';
    }

    echo '
    </div>
    
    <div class="mohaa-teams-grid">';

    foreach ($context['mohaa_teams'] as $rank => $team) {
        echo '
        <div class="mohaa-team-card windowbg">
            <div class="team-rank">#', ($rank + 1), '</div>
            <div class="team-header">
                <div class="team-logo">';
        
        if (!empty($team['logo_url'])) {
            echo '<img src="', htmlspecialchars($team['logo_url']), '" alt="', htmlspecialchars($team['team_name']), '" />';
        } else {
            echo '<div class="default-logo">', strtoupper(substr($team['team_name'], 0, 2)), '</div>';
        }

        echo '
                </div>
                <div class="team-info">
                    <h4>
                        <a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

        if (!empty($team['team_tag'])) {
            echo '[', htmlspecialchars($team['team_tag']), '] ';
        }

        echo htmlspecialchars($team['team_name']), '</a>
                    </h4>
                    <span class="team-captain">Captain: ', htmlspecialchars($team['captain_name']), '</span>
                </div>
            </div>
            <div class="team-stats">
                <div class="stat">
                    <span class="value">', $team['rating'], '</span>
                    <span class="label">Rating</span>
                </div>
                <div class="stat">
                    <span class="value">', $team['wins'], '-', $team['losses'], '</span>
                    <span class="label">W/L</span>
                </div>
                <div class="stat">
                    <span class="value">', $team['member_count'], '</span>
                    <span class="label">Members</span>
                </div>
            </div>
        </div>';
    }

    if (empty($context['mohaa_teams'])) {
        echo '
        <div class="windowbg">
            <p class="centertext">', $txt['mohaa_no_teams'], '</p>
        </div>';
    }

    echo '
    </div>
    
    <style>
        .mohaa-teams-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 15px; }
        .mohaa-team-card { padding: 20px; border-radius: 8px; position: relative; }
        .team-rank { position: absolute; top: 10px; right: 10px; background: #4a5d23; color: white; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
        .team-header { display: flex; gap: 15px; margin-bottom: 15px; }
        .team-logo img, .default-logo { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .default-logo { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.5em; }
        .team-info h4 { margin: 0 0 5px; }
        .team-captain { font-size: 0.9em; color: #666; }
        .team-stats { display: flex; justify-content: space-around; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); }
        .team-stats .stat { text-align: center; }
        .team-stats .value { display: block; font-size: 1.2em; font-weight: bold; color: #4a5d23; }
        .team-stats .label { font-size: 0.8em; color: #666; }
    </style>';
}

/**
 * Team view template
 */
function template_mohaa_team_view()
{
    global $context, $txt, $scripturl;

    $team = $context['mohaa_team']['info'];
    $members = $context['mohaa_team']['members'];
    $matches = $context['mohaa_team']['matches'];

    // Team header
    echo '
    <div class="mohaa-team-header windowbg">
        <div class="team-logo-large">';

    if (!empty($team['logo_url'])) {
        echo '<img src="', htmlspecialchars($team['logo_url']), '" alt="', htmlspecialchars($team['team_name']), '" />';
    } else {
        echo '<div class="default-logo-large">', strtoupper(substr($team['team_name'], 0, 2)), '</div>';
    }

    echo '
        </div>
        <div class="team-details">
            <h2>';

    if (!empty($team['team_tag'])) {
        echo '<span class="team-tag">[', htmlspecialchars($team['team_tag']), ']</span> ';
    }

    echo htmlspecialchars($team['team_name']), '</h2>
            <p class="description">', nl2br(htmlspecialchars($team['description'] ?: $txt['mohaa_no_description'])), '</p>
            <div class="team-meta">
                <span>👑 Captain: <a href="', $scripturl, '?action=profile;u=', $team['id_captain'], '">', htmlspecialchars($team['captain_name']), '</a></span>
                <span>📅 Founded: ', timeformat($team['founded_date']), '</span>
            </div>
        </div>
        <div class="team-stats-large">
            <div class="stat">
                <div class="value">', $team['rating'], '</div>
                <div class="label">Rating</div>
            </div>
            <div class="stat">
                <div class="value">', $team['wins'], '</div>
                <div class="label">Wins</div>
            </div>
            <div class="stat">
                <div class="value">', $team['losses'], '</div>
                <div class="label">Losses</div>
            </div>
            <div class="stat">
                <div class="value">', $team['draws'], '</div>
                <div class="label">Draws</div>
            </div>
        </div>
    </div>';

    // Action buttons
    echo '
    <div class="windowbg" style="text-align: right;">';

    if ($context['mohaa_team']['can_join']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=join;id=', $team['id_team'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_request_join'], '</a>';
    } elseif ($context['mohaa_team']['has_pending']) {
        echo '<span class="infobox">', $txt['mohaa_request_pending'], '</span>';
    }

    if ($context['mohaa_team']['my_membership'] && !$context['mohaa_team']['is_captain']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=leave;id=', $team['id_team'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" onclick="return confirm(\'', $txt['mohaa_leave_confirm'], '\');">', $txt['mohaa_leave_team'], '</a>';
    }

    if ($context['mohaa_team']['is_officer']) {
        echo '
        <a href="', $scripturl, '?action=mohaateams;sa=manage;id=', $team['id_team'], '" class="button">', $txt['mohaa_manage'], '</a>';
    }

    echo '
    </div>';

    // Members
    echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_roster'], ' (', count($members), ')</h4></div>
    <div class="mohaa-roster windowbg">';

    foreach ($members as $m) {
        $roleIcon = match($m['role']) {
            'captain' => '👑',
            'officer' => '⭐',
            'substitute' => '🔄',
            default => '🎮',
        };

        echo '
        <div class="roster-member">
            <div class="member-avatar">';

        if (!empty($m['avatar'])) {
            echo '<img src="', $m['avatar'], '" alt="" />';
        } else {
            echo '<div class="default-avatar">', strtoupper(substr($m['member_name'], 0, 1)), '</div>';
        }

        echo '
            </div>
            <div class="member-info">
                <a href="', $scripturl, '?action=profile;u=', $m['id_member'], '">', htmlspecialchars($m['real_name'] ?: $m['member_name']), '</a>
                <span class="role">', $roleIcon, ' ', ucfirst($m['role']), '</span>
            </div>
        </div>';
    }

    echo '
    </div>';

    // Match history
    if (!empty($matches)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_match_history'], '</h4></div>
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>', $txt['mohaa_result'], '</th>
                    <th>', $txt['mohaa_opponent'], '</th>
                    <th>', $txt['mohaa_score'], '</th>
                    <th>', $txt['mohaa_map'], '</th>
                    <th>', $txt['mohaa_date'], '</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($matches as $match) {
            $resultClass = match($match['result']) {
                'win' => 'result-win',
                'loss' => 'result-loss',
                default => 'result-draw',
            };

            echo '
                <tr class="windowbg">
                    <td><span class="match-result ', $resultClass, '">', strtoupper($match['result']), '</span></td>
                    <td>';

            if (!empty($match['id_opponent'])) {
                echo '<a href="', $scripturl, '?action=mohaateams;sa=view;id=', $match['id_opponent'], '">', htmlspecialchars($match['opponent_team_name']), '</a>';
            } else {
                echo htmlspecialchars($match['opponent_name'] ?: 'Unknown');
            }

            echo '</td>
                    <td><strong>', $match['team_score'], ' - ', $match['opponent_score'], '</strong></td>
                    <td>', htmlspecialchars($match['map'] ?: '-'), '</td>
                    <td>', timeformat($match['match_date']), '</td>
                </tr>';
        }

        echo '
            </tbody>
        </table>';
    }

    echo '
    <style>
        .mohaa-team-header { display: grid; grid-template-columns: 120px 1fr 200px; gap: 25px; align-items: start; padding: 25px; }
        .team-logo-large img, .default-logo-large { width: 120px; height: 120px; border-radius: 12px; }
        .default-logo-large { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-size: 3em; font-weight: bold; }
        .team-details h2 { margin: 0 0 10px; }
        .team-tag { color: #4a5d23; }
        .team-meta span { display: block; margin-top: 5px; color: #666; }
        .team-stats-large { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .team-stats-large .stat { background: #f5f5f5; padding: 15px; text-align: center; border-radius: 8px; }
        .team-stats-large .value { font-size: 1.8em; font-weight: bold; color: #4a5d23; }
        .team-stats-large .label { color: #666; font-size: 0.9em; }
        .mohaa-roster { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .roster-member { display: flex; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
        .member-avatar img, .default-avatar { width: 45px; height: 45px; border-radius: 50%; }
        .default-avatar { background: #4a5d23; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .member-info a { display: block; font-weight: bold; }
        .member-info .role { font-size: 0.85em; color: #666; }
        .match-result { padding: 3px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .result-win { background: #4ade80; color: #166534; }
        .result-loss { background: #f87171; color: #991b1b; }
        .result-draw { background: #fbbf24; color: #92400e; }
    </style>';
}

/**
 * Create team template
 */
function template_mohaa_team_create()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_create_team'], '</h3>
    </div>';

    if (!empty($context['mohaa_error'])) {
        echo '<div class="errorbox">', $context['mohaa_error'], '</div>';
    }

    echo '
    <form action="', $scripturl, '?action=mohaateams;sa=create" method="post">
        <div class="windowbg">
            <dl class="settings">
                <dt><label for="team_name">', $txt['mohaa_team_name'], ' <span class="required">*</span></label></dt>
                <dd><input type="text" name="team_name" id="team_name" size="50" maxlength="100" required /></dd>
                
                <dt><label for="team_tag">', $txt['mohaa_team_tag'], '</label></dt>
                <dd><input type="text" name="team_tag" id="team_tag" size="10" maxlength="10" placeholder="e.g. ABC" /></dd>
                
                <dt><label for="description">', $txt['mohaa_description'], '</label></dt>
                <dd><textarea name="description" id="description" rows="4" cols="50"></textarea></dd>
                
                <dt><label for="logo_url">', $txt['mohaa_logo_url'], '</label></dt>
                <dd><input type="url" name="logo_url" id="logo_url" size="50" placeholder="https://..." /></dd>
            </dl>
        </div>
        <div class="windowbg" style="text-align: right;">
            <input type="submit" name="create_team" value="', $txt['mohaa_create'], '" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </div>
    </form>';
}

/**
 * Team manage template
 */
function template_mohaa_team_manage()
{
    global $context, $txt, $scripturl;

    $team = $context['mohaa_team_manage']['team'];
    $members = $context['mohaa_team_manage']['members'];
    $requests = $context['mohaa_team_manage']['requests'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_manage'], ': ', htmlspecialchars($team['team_name']), '</h3>
    </div>';

    // Pending join requests
    if (!empty($requests)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_join_requests'], ' (', count($requests), ')</h4></div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <tbody>';

        foreach ($requests as $req) {
            echo '
                    <tr class="windowbg">
                        <td><a href="', $scripturl, '?action=profile;u=', $req['id_member'], '">', htmlspecialchars($req['member_name']), '</a></td>
                        <td>', timeformat($req['created_date']), '</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                                <input type="hidden" name="invite_id" value="', $req['id_invite'], '" />
                                <button type="submit" name="action" value="approve_request" class="button">', $txt['mohaa_approve'], '</button>
                            </form>
                        </td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>';
    }

    // Current members
    echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_members'], '</h4></div>
    <div class="windowbg">
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>', $txt['mohaa_player'], '</th>
                    <th>', $txt['mohaa_role'], '</th>
                    <th>', $txt['mohaa_joined'], '</th>
                    <th>', $txt['mohaa_actions'], '</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($members as $m) {
        echo '
                <tr class="windowbg">
                    <td><a href="', $scripturl, '?action=profile;u=', $m['id_member'], '">', htmlspecialchars($m['member_name']), '</a></td>
                    <td>', ucfirst($m['role']), '</td>
                    <td>', timeformat($m['joined_date']), '</td>
                    <td>';

        if ($m['role'] !== 'captain') {
            echo '
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                            <input type="hidden" name="member_id" value="', $m['id_member'], '" />
                            <select name="role" onchange="this.form.action.value=\'promote\'; this.form.submit();">
                                <option value="member" ', $m['role'] === 'member' ? 'selected' : '', '>Member</option>
                                <option value="officer" ', $m['role'] === 'officer' ? 'selected' : '', '>Officer</option>
                                <option value="substitute" ', $m['role'] === 'substitute' ? 'selected' : '', '>Substitute</option>
                            </select>
                            <input type="hidden" name="action" value="" />
                            <button type="submit" name="action" value="kick" class="button" onclick="return confirm(\'', $txt['mohaa_kick_confirm'], '\');">', $txt['mohaa_kick'], '</button>
                        </form>';
        }

        echo '
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

/**
 * Team rankings template
 */
function template_mohaa_team_rankings()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_team_rankings'], '</h3>
    </div>
    
    <table class="table_grid" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th>#</th>
                <th>', $txt['mohaa_team'], '</th>
                <th>', $txt['mohaa_rating'], '</th>
                <th>W/L</th>
                <th>', $txt['mohaa_members'], '</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($context['mohaa_teams_ranking'] as $rank => $team) {
        echo '
            <tr class="windowbg">
                <td><strong>', ($rank + 1), '</strong></td>
                <td>
                    <a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

        if (!empty($team['team_tag'])) {
            echo '[', htmlspecialchars($team['team_tag']), '] ';
        }

        echo htmlspecialchars($team['team_name']), '</a>
                </td>
                <td><strong>', $team['rating'], '</strong></td>
                <td>', $team['wins'], '-', $team['losses'], '</td>
                <td>', $team['member_count'], '</td>
            </tr>';
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Profile teams template
 */
function template_mohaa_profile_teams()
{
    global $context, $txt, $scripturl;

    $teams = $context['mohaa_profile_teams']['teams'];
    $invites = $context['mohaa_profile_teams']['invites'];
    $isOwn = $context['mohaa_profile_teams']['is_own'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_my_teams'], '</h3>
    </div>';

    // Pending invites
    if ($isOwn && !empty($invites)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">📨 ', $txt['mohaa_team_invites'], '</h4></div>
        <div class="windowbg">';

        foreach ($invites as $inv) {
            echo '
            <div class="invite-card" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee;">
                <div>
                    <strong><a href="', $scripturl, '?action=mohaateams;sa=view;id=', $inv['id_team'], '">', htmlspecialchars($inv['team_name']), '</a></strong>
                    <div style="color: #666;">Invited by ', htmlspecialchars($inv['inviter_name']), ' on ', timeformat($inv['created_date']), '</div>
                </div>
                <div>
                    <a href="', $scripturl, '?action=mohaateams;sa=accept;invite=', $inv['id_invite'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_accept'], '</a>
                    <a href="', $scripturl, '?action=mohaateams;sa=decline;invite=', $inv['id_invite'], ';', $context['session_var'], '=', $context['session_id'], '" class="button">', $txt['mohaa_decline'], '</a>
                </div>
            </div>';
        }

        echo '
        </div>';
    }

    // Teams
    if (!empty($teams)) {
        echo '
        <div class="windowbg">
            <div class="mohaa-teams-grid">';

        foreach ($teams as $team) {
            echo '
                <div class="mohaa-team-card windowbg2">
                    <h4><a href="', $scripturl, '?action=mohaateams;sa=view;id=', $team['id_team'], '">';

            if (!empty($team['team_tag'])) {
                echo '[', htmlspecialchars($team['team_tag']), '] ';
            }

            echo htmlspecialchars($team['team_name']), '</a></h4>
                    <div>Role: <strong>', ucfirst($team['role']), '</strong></div>
                    <div>Rating: <strong>', $team['rating'], '</strong></div>
                    <div>Record: ', $team['wins'], '-', $team['losses'], '-', $team['draws'], '</div>
                    <div style="color: #666;">Joined ', timeformat($team['joined_date']), '</div>
                </div>';
        }

        echo '
            </div>
        </div>';
    } else {
        echo '
        <div class="windowbg">
            <p class="centertext">', $isOwn ? $txt['mohaa_no_teams_member'] : $txt['mohaa_user_no_teams'], '</p>
        </div>';
    }

    // Create team button
    if ($isOwn) {
        echo '
        <div class="windowbg" style="text-align: center;">
            <a href="', $scripturl, '?action=mohaateams;sa=create" class="button">', $txt['mohaa_create_team'], '</a>
        </div>';
    }
}
