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

    // Fix: Access players from nested structure
    $leaderboardData = $context['mohaa_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['players'] ?? [];
    $current_stat = $leaderboardData['stat'] ?? 'kills';
    $current_period = $leaderboardData['period'] ?? 'all';
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_leaderboards'] ?? 'Leaderboards', '</h3>
    </div>';

    // Filters - Using stat chips for quick switching
    echo '
    <div class="windowbg mohaa-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 15px;">
        <span style="font-weight: bold;">Sort:</span>
        <div class="mohaa-filter-chips" style="display: flex; gap: 5px;">';
    
    $stats = ['kills' => 'Kills', 'kd' => 'K/D', 'headshots' => 'Headshots', 'accuracy' => 'Accuracy', 'playtime' => 'Playtime'];
    foreach ($stats as $key => $label) {
        $active = ($current_stat === $key) ? 'background: #4a6b8a; color: #fff;' : 'background: rgba(0,0,0,0.1);';
        echo '
            <a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" 
               style="padding: 6px 12px; border-radius: 20px; text-decoration: none; ', $active, '">', $label, '</a>';
    }
    echo '
        </div>
        
        <span style="margin-left: 20px; font-weight: bold;">Period:</span>
        <div class="mohaa-filter-chips" style="display: flex; gap: 5px;">';
    
    $periods = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'today' => 'Today'];
    foreach ($periods as $key => $label) {
        $active = ($current_period === $key) ? 'background: #4a6b8a; color: #fff;' : 'background: rgba(0,0,0,0.1);';
        echo '
            <a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $current_stat, ';period=', $key, '" 
               style="padding: 6px 12px; border-radius: 20px; text-decoration: none; ', $active, '">', $label, '</a>';
    }
    echo '
        </div>
    </div>';

    // Leaderboard table
    echo '
    <table class="table_grid mohaa-leaderboard" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th class="rank">#</th>
                <th>', $txt['mohaa_player'] ?? 'Player', '</th>
                <th>', $txt['mohaa_kills'] ?? 'Kills', '</th>
                <th>', $txt['mohaa_deaths'] ?? 'Deaths', '</th>
                <th>', $txt['mohaa_kd'] ?? 'K/D', '</th>
                <th>', $txt['mohaa_headshots'] ?? 'HS', '</th>
                <th>', $txt['mohaa_accuracy'] ?? 'Acc%', '</th>
                <th>', $txt['mohaa_playtime'] ?? 'Time', '</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr class="windowbg">
                <td colspan="8" class="centertext" style="padding: 40px;">
                    <div style="font-size: 2em; margin-bottom: 10px;">📊</div>
                    <div>', $txt['mohaa_no_data'] ?? 'No player data available. Play some matches!', '</div>
                </td>
            </tr>';
    } else {
        foreach ($leaderboard as $rank => $player) {
            $rankNum = $rank + 1;
            $rankClass = match($rankNum) {
                1 => 'rank-1',
                2 => 'rank-2',
                3 => 'rank-3',
                default => ''
            };
            
            $kd = $player['deaths'] > 0 ? round($player['kills'] / $player['deaths'], 2) : $player['kills'];
            $kdClass = $kd >= 1 ? 'kd-positive' : 'kd-negative';
            $accuracy = $player['shots_fired'] > 0 
                ? round(($player['shots_hit'] / $player['shots_fired']) * 100, 1) 
                : 0;
            
            echo '
            <tr class="windowbg">
                <td class="rank ', $rankClass, '">';
            
            if ($rankNum === 1) echo '🥇 ';
            elseif ($rankNum === 2) echo '🥈 ';
            elseif ($rankNum === 3) echo '🥉 ';
            
            echo $rankNum, '</td>
                <td>
                    <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">
                        <strong>', $player['name'], '</strong>
                    </a>';
            
            if (!empty($player['verified'])) {
                echo ' <span style="color: #4ade80;" title="Verified">✓</span>';
            }
            
            echo '</td>
                <td>', number_format($player['kills']), '</td>
                <td>', number_format($player['deaths']), '</td>
                <td class="', $kdClass, '">', $kd, '</td>
                <td>', number_format($player['headshots']), '</td>
                <td>', $accuracy, '%</td>
                <td>', format_playtime($player['playtime_seconds'] ?? 0), '</td>
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
