<?php
/**
 * MOHAA Tournaments Templates
 *
 * @package MohaaTournaments
 * @version 1.0.0
 */

/**
 * Tournament list template
 */
function template_mohaa_tournaments_list()
{
    global $context, $txt, $scripturl, $user_info;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_tournaments'], '</h3>
    </div>';

    // Create tournament button
    if (!$user_info['is_guest'] && allowedTo('mohaa_create_tournament')) {
        echo '
        <div class="windowbg" style="text-align: right; padding: 10px;">
            <a href="', $scripturl, '?action=mohaatournaments;sa=create" class="button">', $txt['mohaa_create_tournament'], '</a>
        </div>';
    }

    // Active tournaments
    if (!empty($context['mohaa_tournaments']['active'])) {
        echo '
        <div class="cat_bar"><h4 class="catbg">🔴 ', $txt['mohaa_active_tournaments'], '</h4></div>';
        template_tournament_cards($context['mohaa_tournaments']['active'], 'active');
    }

    // Upcoming (registration open)
    if (!empty($context['mohaa_tournaments']['upcoming'])) {
        echo '
        <div class="cat_bar"><h4 class="catbg">📅 ', $txt['mohaa_upcoming_tournaments'], '</h4></div>';
        template_tournament_cards($context['mohaa_tournaments']['upcoming'], 'upcoming');
    }

    // Completed
    if (!empty($context['mohaa_tournaments']['completed'])) {
        echo '
        <div class="cat_bar"><h4 class="catbg">✅ ', $txt['mohaa_completed_tournaments'], '</h4></div>';
        template_tournament_cards($context['mohaa_tournaments']['completed'], 'completed');
    }

    if (empty($context['mohaa_tournaments']['active']) && empty($context['mohaa_tournaments']['upcoming']) && empty($context['mohaa_tournaments']['completed'])) {
        echo '
        <div class="windowbg">
            <p class="centertext">', $txt['mohaa_no_tournaments'], '</p>
        </div>';
    }
}

/**
 * Tournament cards helper
 */
function template_tournament_cards($tournaments, $type)
{
    global $txt, $scripturl;

    echo '
    <div class="mohaa-tournament-grid">';

    foreach ($tournaments as $t) {
        $statusClass = match($t['status']) {
            'active' => 'status-active',
            'registration' => 'status-registration',
            default => 'status-completed',
        };

        echo '
        <div class="mohaa-tournament-card windowbg ', $statusClass, '">
            <div class="tournament-header">
                <h4><a href="', $scripturl, '?action=mohaatournaments;sa=view;id=', $t['id_tournament'], '">', htmlspecialchars($t['name']), '</a></h4>
                <span class="tournament-type">', ucfirst(str_replace('_', ' ', $t['tournament_type'])), '</span>
            </div>
            <div class="tournament-info">
                <div class="info-row">
                    <span>👥 ', $txt['mohaa_participants'], ':</span>
                    <strong>', $t['participant_count'], '/', $t['max_teams'], '</strong>
                </div>
                <div class="info-row">
                    <span>🎮 ', $txt['mohaa_mode'], ':</span>
                    <strong>', strtoupper($t['game_mode']), '</strong>
                </div>
                <div class="info-row">
                    <span>👤 ', $txt['mohaa_team_size'], ':</span>
                    <strong>', $t['team_size'] == 1 ? '1v1' : $t['team_size'] . 'v' . $t['team_size'], '</strong>
                </div>';

        if ($t['status'] === 'registration' && $t['registration_end'] > 0) {
            echo '
                <div class="info-row">
                    <span>⏰ ', $txt['mohaa_reg_closes'], ':</span>
                    <strong>', timeformat($t['registration_end']), '</strong>
                </div>';
        } elseif ($t['tournament_start'] > 0) {
            echo '
                <div class="info-row">
                    <span>📅 ', $txt['mohaa_starts'], ':</span>
                    <strong>', timeformat($t['tournament_start']), '</strong>
                </div>';
        }

        echo '
            </div>
            <div class="tournament-actions">
                <a href="', $scripturl, '?action=mohaatournaments;sa=view;id=', $t['id_tournament'], '" class="button">', $txt['mohaa_view_details'], '</a>';

        if ($t['status'] === 'active') {
            echo '
                <a href="', $scripturl, '?action=mohaatournaments;sa=bracket;id=', $t['id_tournament'], '" class="button">', $txt['mohaa_view_bracket'], '</a>';
        }

        echo '
            </div>
        </div>';
    }

    echo '
    </div>
    
    <style>
        .mohaa-tournament-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 15px 0; }
        .mohaa-tournament-card { padding: 20px; border-radius: 8px; border-left: 4px solid #ccc; }
        .mohaa-tournament-card.status-active { border-left-color: #ef4444; }
        .mohaa-tournament-card.status-registration { border-left-color: #4ade80; }
        .mohaa-tournament-card.status-completed { border-left-color: #6b7280; }
        .tournament-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .tournament-header h4 { margin: 0; }
        .tournament-type { background: #4a5d23; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; }
        .tournament-info { margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .tournament-actions { display: flex; gap: 10px; }
    </style>';
}

/**
 * Tournament view template
 */
function template_mohaa_tournament_view()
{
    global $context, $txt, $scripturl, $user_info;

    $t = $context['mohaa_tournament']['info'];
    $participants = $context['mohaa_tournament']['participants'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', htmlspecialchars($t['name']), '</h3>
    </div>';

    // Tournament header
    echo '
    <div class="windowbg">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div>
                <div class="description">', nl2br(htmlspecialchars($t['description'])), '</div>
                
                <h4 style="margin-top: 20px;">', $txt['mohaa_rules'], '</h4>
                <div class="rules">', nl2br(htmlspecialchars($t['rules'] ?: $txt['mohaa_no_rules'])), '</div>';

    if (!empty($t['prize_info'])) {
        echo '
                <h4 style="margin-top: 20px;">🏆 ', $txt['mohaa_prizes'], '</h4>
                <div class="prizes">', nl2br(htmlspecialchars($t['prize_info'])), '</div>';
    }

    echo '
            </div>
            <div>
                <div class="mohaa-stat-cards compact">
                    <div class="mohaa-stat-card small">
                        <div class="stat-value">', count(array_filter($participants, fn($p) => $p['status'] === 'approved')), '/', $t['max_teams'], '</div>
                        <div class="stat-label">', $txt['mohaa_participants'], '</div>
                    </div>
                    <div class="mohaa-stat-card small">
                        <div class="stat-value">', ucfirst($t['status']), '</div>
                        <div class="stat-label">', $txt['mohaa_status'], '</div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">';

    // Action buttons
    if ($context['mohaa_tournament']['can_register']) {
        echo '
                    <a href="', $scripturl, '?action=mohaatournaments;sa=register;id=', $t['id_tournament'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" style="width: 100%; text-align: center;">', $txt['mohaa_register'], '</a>';
    } elseif ($context['mohaa_tournament']['can_withdraw']) {
        echo '
                    <a href="', $scripturl, '?action=mohaatournaments;sa=withdraw;id=', $t['id_tournament'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" style="width: 100%; text-align: center;" onclick="return confirm(\'', $txt['mohaa_withdraw_confirm'], '\');">', $txt['mohaa_withdraw'], '</a>';
    } elseif (!empty($context['mohaa_tournament']['my_registration'])) {
        echo '
                    <div class="infobox">', $txt['mohaa_already_registered'], '</div>';
    }

    if ($context['mohaa_tournament']['is_admin']) {
        echo '
                    <a href="', $scripturl, '?action=mohaatournaments;sa=manage;id=', $t['id_tournament'], '" class="button" style="width: 100%; text-align: center; margin-top: 10px;">', $txt['mohaa_manage'], '</a>';
    }

    if ($t['status'] === 'active') {
        echo '
                    <a href="', $scripturl, '?action=mohaatournaments;sa=bracket;id=', $t['id_tournament'], '" class="button" style="width: 100%; text-align: center; margin-top: 10px;">', $txt['mohaa_view_bracket'], '</a>';
    }

    echo '
                </div>
            </div>
        </div>
    </div>';

    // Participants list
    echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_participants'], '</h4></div>
    <div class="windowbg">
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>#</th>
                    <th>', $txt['mohaa_player'], '</th>
                    <th>', $txt['mohaa_status'], '</th>
                    <th>', $txt['mohaa_registered'], '</th>
                </tr>
            </thead>
            <tbody>';

    $approved = array_filter($participants, fn($p) => $p['status'] === 'approved');
    $num = 1;
    foreach ($approved as $p) {
        echo '
                <tr class="windowbg">
                    <td>', $num++, '</td>
                    <td><a href="', $scripturl, '?action=profile;u=', $p['id_member'], '">', htmlspecialchars($p['real_name'] ?: $p['member_name']), '</a></td>
                    <td><span class="status-approved">✓ ', $txt['mohaa_approved'], '</span></td>
                    <td>', timeformat($p['registered_date']), '</td>
                </tr>';
    }

    if (empty($approved)) {
        echo '
                <tr class="windowbg">
                    <td colspan="4" class="centertext">', $txt['mohaa_no_participants'], '</td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

/**
 * Create tournament template
 */
function template_mohaa_tournament_create()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_create_tournament'], '</h3>
    </div>';

    if (!empty($context['mohaa_error'])) {
        echo '<div class="errorbox">', $context['mohaa_error'], '</div>';
    }

    echo '
    <form action="', $scripturl, '?action=mohaatournaments;sa=create" method="post">
        <div class="windowbg">
            <dl class="settings">
                <dt><label for="name">', $txt['mohaa_tournament_name'], ' <span class="required">*</span></label></dt>
                <dd><input type="text" name="name" id="name" size="50" required /></dd>
                
                <dt><label for="description">', $txt['mohaa_description'], '</label></dt>
                <dd><textarea name="description" id="description" rows="4" cols="50"></textarea></dd>
                
                <dt><label for="tournament_type">', $txt['mohaa_tournament_type'], '</label></dt>
                <dd>
                    <select name="tournament_type" id="tournament_type">
                        <option value="single_elim">', $txt['mohaa_single_elim'], '</option>
                        <option value="double_elim">', $txt['mohaa_double_elim'], '</option>
                        <option value="round_robin">', $txt['mohaa_round_robin'], '</option>
                    </select>
                </dd>
                
                <dt><label for="team_size">', $txt['mohaa_team_size'], '</label></dt>
                <dd>
                    <select name="team_size" id="team_size">
                        <option value="1">1v1</option>
                        <option value="2">2v2</option>
                        <option value="3">3v3</option>
                        <option value="4">4v4</option>
                        <option value="5">5v5</option>
                        <option value="6">6v6</option>
                    </select>
                </dd>
                
                <dt><label for="max_teams">', $txt['mohaa_max_participants'], '</label></dt>
                <dd><input type="number" name="max_teams" id="max_teams" value="16" min="4" max="128" /></dd>
                
                <dt><label for="game_mode">', $txt['mohaa_game_mode'], '</label></dt>
                <dd>
                    <select name="game_mode" id="game_mode">
                        <option value="tdm">Team Deathmatch</option>
                        <option value="ffa">Free For All</option>
                        <option value="obj">Objective</option>
                        <option value="lib">Liberation</option>
                    </select>
                </dd>
                
                <dt><label for="maps">', $txt['mohaa_maps'], '</label></dt>
                <dd><textarea name="maps" id="maps" rows="3" cols="50" placeholder="One map per line"></textarea></dd>
                
                <dt><label for="rules">', $txt['mohaa_rules'], '</label></dt>
                <dd><textarea name="rules" id="rules" rows="5" cols="50"></textarea></dd>
                
                <dt><label for="prize_info">', $txt['mohaa_prizes'], '</label></dt>
                <dd><textarea name="prize_info" id="prize_info" rows="3" cols="50"></textarea></dd>
                
                <dt><label for="registration_start">', $txt['mohaa_reg_start'], '</label></dt>
                <dd><input type="datetime-local" name="registration_start" id="registration_start" /></dd>
                
                <dt><label for="registration_end">', $txt['mohaa_reg_end'], '</label></dt>
                <dd><input type="datetime-local" name="registration_end" id="registration_end" /></dd>
                
                <dt><label for="tournament_start">', $txt['mohaa_tournament_start'], '</label></dt>
                <dd><input type="datetime-local" name="tournament_start" id="tournament_start" /></dd>
            </dl>
        </div>
        <div class="windowbg" style="text-align: right;">
            <input type="submit" name="create_tournament" value="', $txt['mohaa_create'], '" class="button" />
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
        </div>
    </form>';
}

/**
 * Tournament bracket template
 */
function template_mohaa_tournament_bracket()
{
    global $context, $txt, $scripturl;

    $bracket = $context['mohaa_bracket'];
    $t = $bracket['tournament'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', htmlspecialchars($t['name']), ' - ', $txt['mohaa_bracket'], '</h3>
    </div>
    
    <div class="windowbg">
        <div class="bracket-container">';

    for ($round = 1; $round <= $bracket['rounds']; $round++) {
        $matches = $bracket['matches'][$round] ?? [];
        $roundName = $round == $bracket['rounds'] ? $txt['mohaa_finals'] : ($round == $bracket['rounds'] - 1 ? $txt['mohaa_semifinals'] : $txt['mohaa_round'] . ' ' . $round);

        echo '
            <div class="bracket-round">
                <div class="round-header">', $roundName, '</div>';

        foreach ($matches as $match) {
            $p1Class = $match['id_winner'] == $match['id_player1'] ? 'winner' : '';
            $p2Class = $match['id_winner'] == $match['id_player2'] ? 'winner' : '';

            echo '
                <div class="bracket-match ', $match['status'], '">
                    <div class="match-player ', $p1Class, '">
                        <span class="player-name">', $match['player1_name'] ?: 'TBD', '</span>
                        <span class="player-score">', $match['team1_score'], '</span>
                    </div>
                    <div class="match-player ', $p2Class, '">
                        <span class="player-name">', $match['player2_name'] ?: 'TBD', '</span>
                        <span class="player-score">', $match['team2_score'], '</span>
                    </div>
                </div>';
        }

        echo '
            </div>';
    }

    echo '
        </div>
    </div>
    
    <style>
        .bracket-container { display: flex; gap: 30px; overflow-x: auto; padding: 20px 0; }
        .bracket-round { display: flex; flex-direction: column; justify-content: space-around; min-width: 200px; }
        .round-header { text-align: center; font-weight: bold; margin-bottom: 15px; padding: 8px; background: #4a5d23; color: white; border-radius: 4px; }
        .bracket-match { background: #f5f5f5; border-radius: 4px; margin-bottom: 20px; overflow: hidden; }
        .bracket-match.completed { border-left: 3px solid #4ade80; }
        .bracket-match.pending { border-left: 3px solid #fbbf24; }
        .match-player { display: flex; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #e0e0e0; }
        .match-player:last-child { border-bottom: none; }
        .match-player.winner { background: rgba(74, 222, 128, 0.2); font-weight: bold; }
        .player-score { font-weight: bold; min-width: 20px; text-align: right; }
    </style>';
}

/**
 * Tournament manage template
 */
function template_mohaa_tournament_manage()
{
    global $context, $txt, $scripturl;

    $t = $context['mohaa_manage']['tournament'];
    $participants = $context['mohaa_manage']['participants'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_manage'], ': ', htmlspecialchars($t['name']), '</h3>
    </div>';

    // Status controls
    echo '
    <div class="windowbg">
        <h4>', $txt['mohaa_tournament_status'], ': <strong>', ucfirst($t['status']), '</strong></h4>
        <form method="post">
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';

    if ($t['status'] === 'draft') {
        echo '
            <button type="submit" name="action" value="open_registration" class="button">', $txt['mohaa_open_registration'], '</button>';
    } elseif ($t['status'] === 'registration') {
        echo '
            <button type="submit" name="action" value="close_registration" class="button">', $txt['mohaa_close_registration'], '</button>
            <a href="', $scripturl, '?action=mohaatournaments;sa=start;id=', $t['id_tournament'], ';', $context['session_var'], '=', $context['session_id'], '" class="button" onclick="return confirm(\'', $txt['mohaa_start_confirm'], '\');">', $txt['mohaa_start_tournament'], '</a>';
    }

    echo '
        </form>
    </div>';

    // Participants management
    echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_participants'], ' (', count($participants), ')</h4></div>
    <div class="windowbg">
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>', $txt['mohaa_player'], '</th>
                    <th>', $txt['mohaa_status'], '</th>
                    <th>', $txt['mohaa_actions'], '</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($participants as $p) {
        echo '
                <tr class="windowbg">
                    <td>', htmlspecialchars($p['member_name']), '</td>
                    <td>', ucfirst($p['status']), '</td>
                    <td>
                        <!-- Add approve/reject buttons here -->
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

/**
 * Tournaments admin (init/status)
 */
function template_mohaa_tournaments_admin()
{
    global $context, $txt;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_tournaments_admin'], '</h3>
    </div>';

    if (!empty($context['mohaa_admin_notice'])) {
        echo '
        <div class="infobox">', $context['mohaa_admin_notice'], '</div>';
    }

    echo '
    <div class="windowbg">
        <h4>', $txt['mohaa_tournament_status'], '</h4>
        <ul>';

    foreach ($context['mohaa_admin']['tables'] as $table => $exists) {
        echo '
            <li><strong>', $table, ':</strong> ', $exists ? 'OK' : 'MISSING', '</li>';
    }

    echo '
        </ul>
        <p>', $txt['mohaa_tournaments'], ': <strong>', $context['mohaa_admin']['tournament_count'], '</strong></p>
    </div>

    <div class="windowbg" style="margin-top: 15px;">
        <form method="post">
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
            <button type="submit" class="button" name="mohaa_action" value="seed_demo">', $txt['mohaa_init_tournaments'], '</button>
        </form>
    </div>';
}

/**
 * Individual match view/report template
 */
function template_mohaa_tournament_match()
{
    global $context, $txt, $scripturl;
    
    $match = $context['mohaa_match']['info'];
    $p1 = $context['mohaa_match']['player1'];
    $p2 = $context['mohaa_match']['player2'];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', htmlspecialchars($match['tournament_name']), ' - ', $txt['mohaa_round'], ' ', $match['round_number'], '</h3>
    </div>';
    
    if (!empty($context['mohaa_error'])) {
        echo '<div class="errorbox">', $context['mohaa_error'], '</div>';
    }
    
    echo '
    <div class="windowbg">
        <div class="match-detail-card" style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; text-align: center; padding: 30px;">
            <div class="player-side">
                <div class="player-avatar" style="font-size: 48px;">👤</div>';
    
    if ($p1['id']) {
        echo '
                <h3><a href="', $scripturl, '?action=profile;u=', $p1['id'], '">', htmlspecialchars($p1['name']), '</a></h3>';
    } else {
        echo '
                <h3 class="tbd">TBD</h3>';
    }
    
    if ($match['status'] === 'completed') {
        $p1Class = $match['id_winner'] == $p1['id'] ? 'winner' : 'loser';
        echo '
                <div class="score ', $p1Class, '" style="font-size: 48px; font-weight: bold;">', (int)$match['team1_score'], '</div>';
    }
    
    echo '
            </div>
            
            <div class="vs-divider" style="display: flex; align-items: center;">
                <span style="font-size: 24px; font-weight: bold; color: #888;">VS</span>
            </div>
            
            <div class="player-side">
                <div class="player-avatar" style="font-size: 48px;">👤</div>';
    
    if ($p2['id']) {
        echo '
                <h3><a href="', $scripturl, '?action=profile;u=', $p2['id'], '">', htmlspecialchars($p2['name']), '</a></h3>';
    } else {
        echo '
                <h3 class="tbd">TBD</h3>';
    }
    
    if ($match['status'] === 'completed') {
        $p2Class = $match['id_winner'] == $p2['id'] ? 'winner' : 'loser';
        echo '
                <div class="score ', $p2Class, '" style="font-size: 48px; font-weight: bold;">', (int)$match['team2_score'], '</div>';
    }
    
    echo '
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p><strong>', $txt['mohaa_status'], ':</strong> ', ucfirst($match['status']), '</p>';
    
    if ($match['status'] === 'completed' && !empty($match['completed_time'])) {
        echo '
            <p><strong>', $txt['mohaa_completed'], ':</strong> ', timeformat($match['completed_time']), '</p>';
    }
    
    if (!empty($match['scheduled_time'])) {
        echo '
            <p><strong>', $txt['mohaa_scheduled'], ':</strong> ', timeformat($match['scheduled_time']), '</p>';
    }
    
    echo '
        </div>
    </div>';
    
    // Score report form (only if allowed and match is pending)
    if ($context['mohaa_match']['can_report']) {
        echo '
    <div class="cat_bar"><h4 class="catbg">', $txt['mohaa_report_score'], '</h4></div>
    <div class="windowbg">
        <form method="post" style="text-align: center;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 20px; margin: 20px 0;">
                <div>
                    <label>', htmlspecialchars($p1['name']), '</label><br>
                    <input type="number" name="score1" min="0" value="0" style="width: 80px; font-size: 24px; text-align: center;" />
                </div>
                <span style="font-size: 20px;">-</span>
                <div>
                    <label>', htmlspecialchars($p2['name']), '</label><br>
                    <input type="number" name="score2" min="0" value="0" style="width: 80px; font-size: 24px; text-align: center;" />
                </div>
            </div>
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
            <button type="submit" name="report_score" value="1" class="button">', $txt['mohaa_submit_score'], '</button>
        </form>
    </div>';
    }
    
    echo '
    <style>
        .match-detail-card .winner { color: #4ade80; }
        .match-detail-card .loser { color: #888; }
        .match-detail-card .tbd { color: #666; font-style: italic; }
    </style>';
}
