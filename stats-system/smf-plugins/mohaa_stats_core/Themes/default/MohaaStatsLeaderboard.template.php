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

    $leaderboard = $context['mohaa_leaderboard'] ?? [];
    $current_mode = $context['mohaa_mode'] ?? 'all';
    $current_sort = $context['mohaa_sort'] ?? 'kills';
    $current_period = $context['mohaa_period'] ?? 'all';
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_leaderboard'], '</h3>
    </div>';

    // Filters
    echo '
    <div class="windowbg mohaa-filters">
        <form action="', $scripturl, '?action=mohaastats;sa=leaderboard" method="get">
            <input type="hidden" name="action" value="mohaastats" />
            <input type="hidden" name="sa" value="leaderboard" />
            
            <label>
                ', $txt['mohaa_mode'], ':
                <select name="mode" onchange="this.form.submit()">
                    <option value="all"', $current_mode === 'all' ? ' selected' : '', '>', $txt['mohaa_all_modes'], '</option>
                    <option value="ffa"', $current_mode === 'ffa' ? ' selected' : '', '>Free For All</option>
                    <option value="tdm"', $current_mode === 'tdm' ? ' selected' : '', '>Team Deathmatch</option>
                    <option value="obj"', $current_mode === 'obj' ? ' selected' : '', '>Objective</option>
                </select>
            </label>
            
            <label>
                ', $txt['mohaa_sort_by'], ':
                <select name="sort" onchange="this.form.submit()">
                    <option value="kills"', $current_sort === 'kills' ? ' selected' : '', '>', $txt['mohaa_kills'], '</option>
                    <option value="kd"', $current_sort === 'kd' ? ' selected' : '', '>', $txt['mohaa_kd'], '</option>
                    <option value="score"', $current_sort === 'score' ? ' selected' : '', '>', $txt['mohaa_score'], '</option>
                    <option value="headshots"', $current_sort === 'headshots' ? ' selected' : '', '>', $txt['mohaa_headshots'], '</option>
                    <option value="playtime"', $current_sort === 'playtime' ? ' selected' : '', '>', $txt['mohaa_playtime'], '</option>
                </select>
            </label>
            
            <label>
                ', $txt['mohaa_period'], ':
                <select name="period" onchange="this.form.submit()">
                    <option value="all"', $current_period === 'all' ? ' selected' : '', '>', $txt['mohaa_all_time'], '</option>
                    <option value="month"', $current_period === 'month' ? ' selected' : '', '>', $txt['mohaa_this_month'], '</option>
                    <option value="week"', $current_period === 'week' ? ' selected' : '', '>', $txt['mohaa_this_week'], '</option>
                    <option value="today"', $current_period === 'today' ? ' selected' : '', '>', $txt['mohaa_today'], '</option>
                </select>
            </label>
        </form>
    </div>';

    // Leaderboard table
    echo '
    <table class="table_grid mohaa-leaderboard" style="width: 100%;">
        <thead>
            <tr class="title_bar">
                <th class="rank">#</th>
                <th>', $txt['mohaa_player'], '</th>
                <th>', $txt['mohaa_kills'], '</th>
                <th>', $txt['mohaa_deaths'], '</th>
                <th>', $txt['mohaa_kd'], '</th>
                <th>', $txt['mohaa_headshots'], '</th>
                <th>', $txt['mohaa_accuracy'], '</th>
                <th>', $txt['mohaa_playtime'], '</th>
            </tr>
        </thead>
        <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr class="windowbg">
                <td colspan="8" class="centertext">', $txt['mohaa_no_data'], '</td>
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
    if (!empty($context['mohaa_page_index'])) {
        echo '
    <div class="pagesection">
        <div class="pagelinks">', $context['mohaa_page_index'], '</div>
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
