<?php
/**
 * MOHAA Server Stats Templates
 *
 * @package MohaaServers
 * @version 1.0.0
 */

/**
 * Server list template
 */
function template_mohaa_servers_list()
{
    global $context, $txt, $scripturl;

    $stats = $context['mohaa_server_stats'] ?? [];
    $servers = $context['mohaa_servers'] ?? [];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_servers'], '</h3>
    </div>';

    // Global server stats
    echo '
    <div class="windowbg">
        <div class="mohaa-stat-cards">
            <div class="mohaa-stat-card">
                <div class="stat-value">', count($servers), '</div>
                <div class="stat-label">', $txt['mohaa_total_servers'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', $stats['online_now'] ?? 0, '</div>
                <div class="stat-label">', $txt['mohaa_servers_online'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', $stats['players_online'] ?? 0, '</div>
                <div class="stat-label">', $txt['mohaa_players_online'], '</div>
            </div>
            <div class="mohaa-stat-card">
                <div class="stat-value">', number_format($stats['total_matches'] ?? 0), '</div>
                <div class="stat-label">', $txt['mohaa_total_matches'], '</div>
            </div>
        </div>
    </div>';

    // Server list
    echo '
    <table class="table_grid" style="width: 100%; margin-top: 20px;">
        <thead>
            <tr class="title_bar">
                <th>', $txt['mohaa_status'], '</th>
                <th>', $txt['mohaa_server_name'], '</th>
                <th>', $txt['mohaa_map'], '</th>
                <th>', $txt['mohaa_players'], '</th>
                <th>', $txt['mohaa_gametype'], '</th>
                <th>', $txt['mohaa_actions'], '</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($servers)) {
        echo '
            <tr class="windowbg">
                <td colspan="6" class="centertext">', $txt['mohaa_no_servers'], '</td>
            </tr>';
    } else {
        foreach ($servers as $server) {
            $statusClass = !empty($server['online']) ? 'online' : 'offline';
            $statusIcon = !empty($server['online']) ? '🟢' : '🔴';

            echo '
            <tr class="windowbg">
                <td class="server-status ', $statusClass, '">', $statusIcon, '</td>
                <td>
                    <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id']), '">
                        <strong>', htmlspecialchars($server['name']), '</strong>
                    </a>
                    <br><small>', $server['address'], ':', $server['port'], '</small>
                </td>
                <td>', $server['current_map'] ?? '-', '</td>
                <td>', $server['players'] ?? 0, '/', $server['max_players'] ?? 0, '</td>
                <td>', ucfirst($server['gametype'] ?? 'dm'), '</td>
                <td>
                    <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id']), '" class="button">', $txt['mohaa_view'], '</a>
                </td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Live servers template
 */
function template_mohaa_servers_live()
{
    global $context, $txt, $scripturl;

    $servers = $context['mohaa_live'] ?? [];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🔴 ', $txt['mohaa_live_servers'], '</h3>
    </div>';

    if (empty($servers)) {
        echo '
        <div class="windowbg">
            <p class="centertext">', $txt['mohaa_no_live_servers'], '</p>
        </div>';
        return;
    }

    echo '
    <div class="mohaa-live-servers-grid">';

    foreach ($servers as $server) {
        echo '
        <div class="mohaa-server-card windowbg">
            <div class="server-header">
                <div class="live-indicator"><span class="pulse"></span> LIVE</div>
                <h4>', htmlspecialchars($server['name']), '</h4>
            </div>
            <div class="server-info">
                <div class="server-map">
                    <strong>', $txt['mohaa_map'], ':</strong> ', $server['current_map'], '
                </div>
                <div class="server-players">
                    <strong>', $txt['mohaa_players'], ':</strong> ', $server['players'], '/', $server['max_players'], '
                </div>
                <div class="server-gametype">
                    <strong>', $txt['mohaa_mode'], ':</strong> ', ucfirst($server['gametype']), '
                </div>
            </div>';

        // Show current match scores if team game
        if (!empty($server['team_match'])) {
            echo '
            <div class="server-scores">
                <span class="team-allies">', $txt['mohaa_allies'], ': ', $server['allies_score'], '</span>
                <span class="vs">vs</span>
                <span class="team-axis">', $txt['mohaa_axis'], ': ', $server['axis_score'], '</span>
            </div>';
        }

        // Player list
        if (!empty($server['player_list'])) {
            echo '
            <div class="server-player-list">
                <strong>', $txt['mohaa_current_players'], ':</strong>
                <ul>';
            foreach (array_slice($server['player_list'], 0, 8) as $player) {
                echo '<li>', htmlspecialchars($player['name']), ' (', $player['score'], ')</li>';
            }
            if (count($server['player_list']) > 8) {
                echo '<li>... +', (count($server['player_list']) - 8), ' more</li>';
            }
            echo '
                </ul>
            </div>';
        }

        echo '
            <div class="server-actions">
                <a href="', $scripturl, '?action=mohaaservers;sa=server;id=', urlencode($server['id']), '" class="button">', $txt['mohaa_view_details'], '</a>
            </div>
        </div>';
    }

    echo '
    </div>
    
    <style>
        .mohaa-live-servers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 15px; }
        .mohaa-server-card { padding: 20px; border-radius: 8px; }
        .mohaa-server-card .server-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .mohaa-server-card h4 { margin: 0; }
        .server-info { margin-bottom: 15px; }
        .server-info > div { margin-bottom: 5px; }
        .server-scores { display: flex; justify-content: center; gap: 15px; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px; margin-bottom: 15px; }
        .server-player-list ul { list-style: none; padding: 0; margin: 5px 0; font-size: 0.9em; }
        .server-player-list li { padding: 2px 0; }
    </style>';
}

/**
 * Server detail template
 */
function template_mohaa_server_detail()
{
    global $context, $txt, $scripturl;

    $server = $context['mohaa_server']['info'];
    $currentMatch = $context['mohaa_server']['current_match'];
    $recentMatches = $context['mohaa_server']['recent_matches'] ?? [];
    $topPlayers = $context['mohaa_server']['top_players'] ?? [];

    $statusClass = !empty($server['online']) ? 'online' : 'offline';
    $statusText = !empty($server['online']) ? $txt['mohaa_online'] : $txt['mohaa_offline'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', htmlspecialchars($server['name']), '</h3>
    </div>';

    // Server header
    echo '
    <div class="windowbg mohaa-server-header">
        <div class="server-status-badge ', $statusClass, '">', $statusText, '</div>
        <div class="server-details">
            <p><strong>', $txt['mohaa_address'], ':</strong> ', $server['address'], ':', $server['port'], '</p>
            <p><strong>', $txt['mohaa_gametype'], ':</strong> ', ucfirst($server['gametype'] ?? 'dm'), '</p>
            <p><strong>', $txt['mohaa_max_players'], ':</strong> ', $server['max_players'], '</p>
        </div>
        <div class="server-quick-stats">
            <div class="mohaa-stat-cards compact">
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($server['total_kills'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_kills'], '</div>
                </div>
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($server['total_matches'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_matches'], '</div>
                </div>
                <div class="mohaa-stat-card small">
                    <div class="stat-value">', number_format($server['unique_players'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_unique_players'], '</div>
                </div>
            </div>
        </div>
    </div>';

    // Current match (if live)
    if (!empty($currentMatch)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">🔴 ', $txt['mohaa_current_match'], '</h4></div>
        <div class="windowbg">
            <div class="current-match-info">
                <div class="match-map"><strong>', $txt['mohaa_map'], ':</strong> ', $currentMatch['map_name'], '</div>
                <div class="match-players"><strong>', $txt['mohaa_players'], ':</strong> ', $currentMatch['player_count'], '</div>
                <div class="match-duration"><strong>', $txt['mohaa_duration'], ':</strong> ', gmdate('i:s', $currentMatch['duration'] ?? 0), '</div>
            </div>';

        if (!empty($currentMatch['players'])) {
            echo '
            <table class="table_grid" style="width: 100%; margin-top: 15px;">
                <thead>
                    <tr class="title_bar">
                        <th>', $txt['mohaa_player'], '</th>
                        <th>', $txt['mohaa_score'], '</th>
                        <th>', $txt['mohaa_kills'], '</th>
                        <th>', $txt['mohaa_deaths'], '</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($currentMatch['players'] as $player) {
                echo '
                    <tr class="windowbg">
                        <td><a href="', $scripturl, '?action=mohaaplayer;guid=', urlencode($player['guid']), '">', htmlspecialchars($player['name']), '</a></td>
                        <td>', $player['score'], '</td>
                        <td>', $player['kills'], '</td>
                        <td>', $player['deaths'], '</td>
                    </tr>';
            }

            echo '
                </tbody>
            </table>';
        }

        echo '
        </div>';
    }

    // Player history chart
    $playerHistory = $context['mohaa_server']['player_history'] ?? [];
    if (!empty($playerHistory)) {
        echo '
        <div class="cat_bar"><h4 class="catbg">📈 ', $txt['mohaa_player_history'], '</h4></div>
        <div class="windowbg">
            <div style="height: 200px;">
                <canvas id="playerHistoryChart"></canvas>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            new Chart(document.getElementById("playerHistoryChart"), {
                type: "line",
                data: {
                    labels: ', json_encode($playerHistory['labels'] ?? []), ',
                    datasets: [{
                        label: "Players Online",
                        data: ', json_encode($playerHistory['counts'] ?? []), ',
                        borderColor: "#4a5d23",
                        backgroundColor: "rgba(74, 93, 35, 0.1)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
        </script>';
    }

    // Two columns: Top players | Recent matches
    echo '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">';

    // Top players
    echo '
        <div>
            <div class="cat_bar"><h4 class="catbg">🏆 ', $txt['mohaa_top_players'], '</h4></div>
            <div class="windowbg">
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr class="title_bar">
                            <th>#</th>
                            <th>', $txt['mohaa_player'], '</th>
                            <th>', $txt['mohaa_kills'], '</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($topPlayers as $i => $player) {
        echo '
                        <tr class="windowbg">
                            <td>', ($i + 1), '</td>
                            <td><a href="', $scripturl, '?action=mohaaplayer;guid=', urlencode($player['guid']), '">', htmlspecialchars($player['name']), '</a></td>
                            <td>', number_format($player['kills']), '</td>
                        </tr>';
    }

    echo '
                    </tbody>
                </table>
            </div>
        </div>';

    // Recent matches
    echo '
        <div>
            <div class="cat_bar"><h4 class="catbg">📋 ', $txt['mohaa_recent_matches'], '</h4></div>
            <div class="windowbg">
                <ul class="mohaa-match-list">';

    foreach (array_slice($recentMatches, 0, 10) as $match) {
        echo '
                    <li class="mohaa-match-item">
                        <a href="', $scripturl, '?action=mohaastats;sa=match;id=', $match['id'], '">
                            <span class="match-map">', $match['map_name'], '</span>
                            <span class="match-players">', $match['player_count'], ' players</span>
                            <span class="match-time">', timeformat($match['ended_at'] ?? time()), '</span>
                        </a>
                    </li>';
    }

    echo '
                </ul>
            </div>
        </div>
    </div>';
}

/**
 * Server history template
 */
function template_mohaa_server_history()
{
    global $context, $txt, $scripturl;

    $history = $context['mohaa_history'];
    $server = $context['mohaa_server'] ?? null;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_server_history'], '</h3>
    </div>';

    // Period selector
    echo '
    <div class="windowbg mohaa-filters">
        <form method="get">
            <input type="hidden" name="action" value="mohaaservers" />
            <input type="hidden" name="sa" value="history" />';

    if ($server) {
        echo '<input type="hidden" name="id" value="', htmlspecialchars($server['id']), '" />';
    }

    echo '
            <label>', $txt['mohaa_period'], ':
                <select name="period" onchange="this.form.submit()">
                    <option value="day"', $history['period'] === 'day' ? ' selected' : '', '>', $txt['mohaa_last_24h'], '</option>
                    <option value="week"', $history['period'] === 'week' ? ' selected' : '', '>', $txt['mohaa_last_week'], '</option>
                    <option value="month"', $history['period'] === 'month' ? ' selected' : '', '>', $txt['mohaa_last_month'], '</option>
                </select>
            </label>
        </form>
    </div>';

    // Charts
    echo '
    <div class="windowbg">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>', $txt['mohaa_player_counts'], '</h4>
                <div style="height: 250px;"><canvas id="playerCountChart"></canvas></div>
            </div>
            <div>
                <h4>', $txt['mohaa_match_counts'], '</h4>
                <div style="height: 250px;"><canvas id="matchCountChart"></canvas></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        new Chart(document.getElementById("playerCountChart"), {
            type: "line",
            data: {
                labels: ', json_encode($history['player_counts']['labels'] ?? []), ',
                datasets: [{
                    label: "Peak Players",
                    data: ', json_encode($history['player_counts']['peak'] ?? []), ',
                    borderColor: "#4a5d23",
                    tension: 0.3
                }, {
                    label: "Average",
                    data: ', json_encode($history['player_counts']['average'] ?? []), ',
                    borderColor: "#8b9a4b",
                    borderDash: [5, 5],
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        
        new Chart(document.getElementById("matchCountChart"), {
            type: "bar",
            data: {
                labels: ', json_encode($history['match_counts']['labels'] ?? []), ',
                datasets: [{
                    label: "Matches",
                    data: ', json_encode($history['match_counts']['counts'] ?? []), ',
                    backgroundColor: "#4a5d23"
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    });
    </script>';
}
