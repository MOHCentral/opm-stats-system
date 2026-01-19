<?php
/**
 * MOHAA Stats - Leaderboard Templates
 *
 * @package MohaaStats
 * @version 1.0.0
 */

/**
 * Main leaderboard template
 */
function template_mohaa_stats_leaderboard()
{
    global $context, $scripturl, $txt;

    $leaderboardData = $context['mohaa_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['players'] ?? [];
    $current_stat = $leaderboardData['stat'] ?? 'kills';
    $current_period = $leaderboardData['period'] ?? 'all';
    
    echo '
    <style>
        .mohaa-lb-wrap { font-family: "Segoe UI", sans-serif; }
        .mohaa-filter-section { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
        .mohaa-filter-section strong { min-width: 60px; }
        .mohaa-chip { padding: 5px 12px; border-radius: 16px; text-decoration: none; font-size: 0.85em; transition: all 0.2s; }
        .mohaa-chip:hover { transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .mohaa-chip.active { background: linear-gradient(135deg, #4a6b8a, #5d7d9a); color: #fff; }
        .mohaa-chip.inactive { background: rgba(0,0,0,0.1); }
        .mohaa-lb-table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .mohaa-lb-table th { padding: 10px 8px; text-align: center; font-weight: bold; white-space: nowrap; }
        .mohaa-lb-table td { padding: 8px; text-align: center; }
        .mohaa-lb-table tr:hover { background: rgba(74, 107, 138, 0.1); }
        .rank-1 { background: linear-gradient(90deg, rgba(255,215,0,0.3), transparent) !important; }
        .rank-2 { background: linear-gradient(90deg, rgba(192,192,192,0.3), transparent) !important; }
        .rank-3 { background: linear-gradient(90deg, rgba(205,127,50,0.3), transparent) !important; }
        .stat-positive { color: #4caf50; font-weight: bold; }
        .stat-negative { color: #f44336; }
        .stat-highlight { background: rgba(74, 107, 138, 0.15); font-weight: bold; }
    </style>
    
    <div class="cat_bar">
        <h3 class="catbg">🏆 Global Leaderboards</h3>
    </div>
    
    <div class="windowbg mohaa-lb-wrap" style="padding: 20px;">';

    // COMBAT Stats Group
    echo '
        <div class="mohaa-filter-section">
            <strong>⚔️ Combat:</strong>';
    $combatStats = ['kills' => 'Kills', 'deaths' => 'Deaths', 'kd' => 'K/D', 'headshots' => 'Headshots', 'accuracy' => 'Accuracy', 'damage' => 'Damage'];
    foreach ($combatStats as $key => $label) {
        $class = ($current_stat === $key) ? 'active' : 'inactive';
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" class="mohaa-chip ', $class, '">', $label, '</a>';
    }
    echo '</div>';

    // SPECIAL KILLS Stats Group
    echo '
        <div class="mohaa-filter-section">
            <strong>💀 Special:</strong>';
    $specialStats = ['suicides' => 'Suicides', 'teamkills' => 'Team Kills', 'roadkills' => 'Roadkills', 'bash_kills' => 'Bash Kills', 'grenades' => 'Grenades'];
    foreach ($specialStats as $key => $label) {
        $class = ($current_stat === $key) ? 'active' : 'inactive';
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" class="mohaa-chip ', $class, '">', $label, '</a>';
    }
    echo '</div>';

    // GAME FLOW Stats Group
    echo '
        <div class="mohaa-filter-section">
            <strong>🎮 Game:</strong>';
    $gameStats = ['wins' => 'Wins', 'rounds' => 'Rounds', 'objectives' => 'Objectives', 'playtime' => 'Playtime'];
    foreach ($gameStats as $key => $label) {
        $class = ($current_stat === $key) ? 'active' : 'inactive';
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" class="mohaa-chip ', $class, '">', $label, '</a>';
    }
    echo '</div>';

    // MOVEMENT Stats Group
    echo '
        <div class="mohaa-filter-section">
            <strong>🏃 Move:</strong>';
    $moveStats = ['distance' => 'Distance', 'jumps' => 'Jumps'];
    foreach ($moveStats as $key => $label) {
        $class = ($current_stat === $key) ? 'active' : 'inactive';
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" class="mohaa-chip ', $class, '">', $label, '</a>';
    }
    echo '</div>';

    // PERIOD Filters
    echo '
        <div class="mohaa-filter-section" style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
            <strong>📅 Period:</strong>';
    $periods = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'day' => 'Today'];
    foreach ($periods as $key => $label) {
        $class = ($current_period === $key) ? 'active' : 'inactive';
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $current_stat, ';period=', $key, '" class="mohaa-chip ', $class, '">', $label, '</a>';
    }
    echo '</div>
    </div>';

    // LEADERBOARD TABLE
    echo '
    <table class="table_grid mohaa-lb-table">
        <thead>
            <tr class="title_bar">
                <th>#</th>
                <th style="text-align:left;">Player</th>
                <th>Kills</th>
                <th>Deaths</th>
                <th>K/D</th>
                <th>HS</th>
                <th>Acc%</th>
                <th>Wins</th>
                <th>Rounds</th>
                <th>Obj</th>
                <th>Dist</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr class="windowbg">
                <td colspan="12" class="centertext" style="padding: 50px;">
                    <div style="font-size: 3em; margin-bottom: 15px;">🎮</div>
                    <div style="font-size: 1.2em;">No player data available yet.</div>
                    <div style="opacity: 0.7;">Get playing to climb the ranks!</div>
                </td>
            </tr>';
    } else {
        foreach ($leaderboard as $rank => $player) {
            $rankNum = $rank + 1;
            $rankClass = match($rankNum) {
                1 => 'rank-1', 2 => 'rank-2', 3 => 'rank-3', default => ''
            };
            $rankIcon = match($rankNum) {
                1 => '🥇', 2 => '🥈', 3 => '🥉', default => ''
            };
            
            $kd = $player['deaths'] > 0 ? round($player['kills'] / $player['deaths'], 2) : $player['kills'];
            $kdClass = $kd >= 1 ? 'stat-positive' : 'stat-negative';
            $acc = round($player['accuracy'] ?? 0, 1);
            $dist = round(($player['distance_km'] ?? 0) / 1000, 1); // Convert to KM
            $time = format_playtime($player['playtime_seconds'] ?? 0);
            
            // Highlight column based on current sort
            $highlightCol = $current_stat;
            
            echo '
            <tr class="windowbg ', $rankClass, '">
                <td style="font-weight: bold;">', $rankIcon, ' ', $rankNum, '</td>
                <td style="text-align: left;">
                    <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">
                        <strong>', htmlspecialchars($player['name']), '</strong>
                    </a>
                </td>
                <td class="', ($highlightCol === 'kills' ? 'stat-highlight' : ''), '">', number_format($player['kills']), '</td>
                <td class="', ($highlightCol === 'deaths' ? 'stat-highlight' : ''), '">', number_format($player['deaths']), '</td>
                <td class="', $kdClass, ' ', ($highlightCol === 'kd' ? 'stat-highlight' : ''), '">', $kd, '</td>
                <td class="', ($highlightCol === 'headshots' ? 'stat-highlight' : ''), '">', number_format($player['headshots']), '</td>
                <td class="', ($highlightCol === 'accuracy' ? 'stat-highlight' : ''), '">', $acc, '%</td>
                <td class="', ($highlightCol === 'wins' ? 'stat-highlight' : ''), '">', number_format($player['wins'] ?? 0), '</td>
                <td class="', ($highlightCol === 'rounds' ? 'stat-highlight' : ''), '">', number_format($player['rounds'] ?? 0), '</td>
                <td class="', ($highlightCol === 'objectives' ? 'stat-highlight' : ''), '">', number_format($player['objectives'] ?? 0), '</td>
                <td class="', ($highlightCol === 'distance' ? 'stat-highlight' : ''), '">', $dist, ' km</td>
                <td class="', ($highlightCol === 'playtime' ? 'stat-highlight' : ''), '">', $time, '</td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>';

    // Pagination
    if (!empty($context['page_index'])) {
        echo '
    <div class="pagesection">
        <div class="pagelinks">', $context['page_index'], '</div>
    </div>';
    }
}

/**
 * Weapon leaderboard template
 */
function template_mohaa_stats_weapon_leaderboard()
{
    global $context, $scripturl, $txt;

    $weapon = $context['mohaa_weapon'] ?? '';
    $leaderboard = $context['mohaa_weapon_leaderboard'] ?? [];
    $weapons = $context['mohaa_weapons_list'] ?? [];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_weapon_leaderboard'], '</h3>
    </div>';

    // Weapon selector
    echo '
    <div class="windowbg mohaa-filters">
        <form action="', $scripturl, '?action=mohaastats;sa=weapons" method="get">
            <input type="hidden" name="action" value="mohaastats" />
            <input type="hidden" name="sa" value="weapons" />
            
            <label>
                ', $txt['mohaa_weapon'], ':
                <select name="weapon" onchange="this.form.submit()">';

    foreach ($weapons as $w) {
        $selected = ($w['id'] === $weapon) ? ' selected' : '';
        echo '
                    <option value="', $w['id'], '"', $selected, '>', $w['name'], '</option>';
    }

    echo '
                </select>
            </label>
        </form>
    </div>';

    // Weapon image and stats
    if (!empty($weapon)) {
        $weaponData = $context['mohaa_weapon_data'] ?? [];
        
        echo '
    <div class="windowbg" style="display: grid; grid-template-columns: auto 1fr; gap: 20px; margin-bottom: 20px;">
        <div style="width: 200px;">
            <img src="Themes/default/images/mohaastats/weapons/', $weapon, '.png" alt="', $weaponData['name'] ?? '', '" style="max-width: 100%;" onerror="this.style.display=\'none\'">
        </div>
        <div>
            <h4>', $weaponData['name'] ?? ucfirst($weapon), '</h4>
            <div class="mohaa-stat-cards" style="margin-top: 10px;">
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($weaponData['total_kills'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_total_kills'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', number_format($weaponData['total_headshots'] ?? 0), '</div>
                    <div class="stat-label">', $txt['mohaa_headshots'], '</div>
                </div>
                <div class="mohaa-stat-card">
                    <div class="stat-value">', round($weaponData['avg_accuracy'] ?? 0, 1), '%</div>
                    <div class="stat-label">', $txt['mohaa_avg_accuracy'], '</div>
                </div>
            </div>
        </div>
    </div>';
    }

    // Top players with this weapon
    echo '
    <table class="table_grid" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th>#</th>
                <th>', $txt['mohaa_player'], '</th>
                <th>', $txt['mohaa_kills'], '</th>
                <th>', $txt['mohaa_headshots'], '</th>
                <th>', $txt['mohaa_accuracy'], '</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr class="windowbg">
                <td colspan="5" class="centertext">', $txt['mohaa_no_data'], '</td>
            </tr>';
    } else {
        foreach ($leaderboard as $rank => $player) {
            $accuracy = $player['shots_fired'] > 0 
                ? round(($player['shots_hit'] / $player['shots_fired']) * 100, 1) 
                : 0;
            
            echo '
            <tr class="windowbg">
                <td>', $rank + 1, '</td>
                <td>
                    <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">
                        ', $player['name'], '
                    </a>
                </td>
                <td>', number_format($player['kills']), '</td>
                <td>', number_format($player['headshots']), '</td>
                <td>', $accuracy, '%</td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Map leaderboard template
 */
function template_mohaa_stats_map_leaderboard()
{
    global $context, $scripturl, $txt;

    $map = $context['mohaa_map'] ?? '';
    $maps = $context['mohaa_maps_list'] ?? [];
    $leaderboard = $context['mohaa_map_leaderboard'] ?? [];
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_map_stats'], '</h3>
    </div>';

    // Map selector
    echo '
    <div class="windowbg mohaa-filters">
        <form action="', $scripturl, '?action=mohaastats;sa=maps" method="get">
            <input type="hidden" name="action" value="mohaastats" />
            <input type="hidden" name="sa" value="maps" />
            
            <label>
                ', $txt['mohaa_map'], ':
                <select name="map" onchange="this.form.submit()">';

    foreach ($maps as $m) {
        $selected = ($m['name'] === $map) ? ' selected' : '';
        echo '
                    <option value="', $m['name'], '"', $selected, '>', $m['display_name'] ?? $m['name'], '</option>';
    }

    echo '
                </select>
            </label>
        </form>
    </div>';

    // Map preview and heatmap
    if (!empty($map)) {
        $mapData = $context['mohaa_map_data'] ?? [];
        
        echo '
    <div class="windowbg">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>', $mapData['display_name'] ?? $map, '</h4>
                <div id="map-heatmap" class="mohaa-heatmap-container" style="margin-top: 15px;">
                    <img src="Themes/default/images/mohaastats/maps/', $map, '.jpg" alt="', $map, '" class="map-background">
                </div>
            </div>
            <div>
                <div class="mohaa-stat-cards">
                    <div class="mohaa-stat-card">
                        <div class="stat-value">', number_format($mapData['total_matches'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_matches'], '</div>
                    </div>
                    <div class="mohaa-stat-card">
                        <div class="stat-value">', number_format($mapData['total_kills'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_kills'], '</div>
                    </div>
                    <div class="mohaa-stat-card">
                        <div class="stat-value">', format_playtime($mapData['total_playtime'] ?? 0), '</div>
                        <div class="stat-label">', $txt['mohaa_playtime'], '</div>
                    </div>
                </div>
                
                <h5 style="margin-top: 20px;">', $txt['mohaa_top_players_map'], '</h5>
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr class="title_bar">
                            <th>#</th>
                            <th>', $txt['mohaa_player'], '</th>
                            <th>', $txt['mohaa_kills'], '</th>
                            <th>', $txt['mohaa_kd'], '</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach (array_slice($leaderboard, 0, 10) as $rank => $player) {
            $kd = $player['deaths'] > 0 ? round($player['kills'] / $player['deaths'], 2) : $player['kills'];
            
            echo '
                        <tr class="windowbg">
                            <td>', $rank + 1, '</td>
                            <td>
                                <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">
                                    ', $player['name'], '
                                </a>
                            </td>
                            <td>', number_format($player['kills']), '</td>
                            <td>', $kd, '</td>
                        </tr>';
        }

        echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        var heatmapData = ', json_encode($mapData['heatmap_data'] ?? []), ';
        var mapImage = "Themes/default/images/mohaastats/maps/', $map, '.jpg";
        document.addEventListener("DOMContentLoaded", function() {
            MohaaStats.initHeatmap("map-heatmap", mapImage, heatmapData.kills || [], "kills");
        });
    </script>';
    }
}

/**
 * Format playtime helper
 */
function format_playtime($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    }
    return $minutes . 'm';
}

/**
 * Competitive Leaderboard Dashboard
 */
function template_mohaa_stats_dashboard()
{
    global $context, $txt, $scripturl;

    $cards = $context['mohaa_dashboard_cards'] ?? [];
    
    echo '
    <div class="mohaa-dashboard">
        <div class="war-room-header">
            <div class="header-title">
                <h1>🏆 COMPETITIVE LEADERBOARDS</h1>
                <span class="header-subtitle">Performance Analysis & Global Records</span>
            </div>
            <div class="header-actions">
                <a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=kills" class="button">📋 View Detailed Table</a>
            </div>
        </div>';

    // Combat Section
    if (!empty($cards['combat'])) {
        echo '<h3 class="category_header" style="margin-top: 20px;">⚔️ Combat Records</h3>
              <div class="mohaa-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">';
        foreach ($cards['combat'] as $card) template_mohaa_stat_card($card);
        echo '</div>';
    }

    // Game Flow Section
    if (!empty($cards['game_flow'])) {
        echo '<h3 class="category_header" style="margin-top: 20px;">🎮 Game Flow</h3>
              <div class="mohaa-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">';
        foreach ($cards['game_flow'] as $card) template_mohaa_stat_card($card);
        echo '</div>';
    }

    // Niche Section
    if (!empty($cards['niche'])) {
        echo '<h3 class="category_header" style="margin-top: 20px;">🌟 Special Records</h3>
              <div class="mohaa-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">';
        foreach ($cards['niche'] as $card) template_mohaa_stat_card($card);
        echo '</div>';
    }

    echo '</div>';
}

function template_mohaa_stat_card($card)
{
    global $scripturl;
    
    $link = $scripturl . '?action=mohaastats;sa=leaderboards;stat=' . $card['metric'];
    
    echo '
    <div class="mohaa-stat-card" onclick="location.href=\'' . $link . '\'" style="cursor: pointer;">
        <div class="card-header">
            <div class="card-icon">' . ($card['icon'] ?? '🏆') . '</div>
            <div class="card-title">' . ($card['title'] ?? 'Stat') . '</div>
        </div>
        
        <ul class="top-list">';
            
    if (!empty($card['top'])) {
        foreach ($card['top'] as $entry) {
            $rankClass = 'rank-' . $entry['rank'];
            $val = isset($entry['display_value']) && $entry['display_value'] !== '' ? $entry['display_value'] : number_format($entry['value']);
            
            // Format floats nicely if display val missing
            if ((!isset($entry['display_value']) || $entry['display_value'] === '') && strpos((string)$entry['value'], '.') !== false) {
                 $val = number_format($entry['value'], 2);
            }

            echo '
            <li class="top-entry ' . $rankClass . '">
                <div style="display: flex; align-items: center; width: 100%;">
                    <span class="rank">' . $entry['rank'] . '.</span>
                    <span class="name">' . htmlspecialchars($entry['name']) . '</span>
                    <span class="value">' . $val . '</span>
                </div>
            </li>';
        }
    } else {
        echo '<li class="top-entry empty" style="justify-content: center; opacity: 0.6;">No records yet</li>';
    }
            
    echo '
        </ul>
        
        <div class="card-footer">
            View Full Leaderboard
        </div>
    </div>';
}
