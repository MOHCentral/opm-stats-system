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
        .mohaa-premium-table { width: 100%; border-collapse: collapse; font-family: "Inter", sans-serif; min-width: 800px; }
        .mohaa-premium-table th { position: sticky; top: 0; background: #1a1f26; color: #fff; padding: 15px; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px; border-bottom: 2px solid #34495e; z-index: 10; text-align: center; }
        .mohaa-premium-table th.player-col { text-align: left; padding-left: 20px; }
        .mohaa-premium-table td { padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cfd8dc; text-align: center; font-variant-numeric: tabular-nums; }
        .mohaa-premium-table tr.top-rank td { background: rgba(255, 215, 0, 0.05); border-bottom: 1px solid rgba(255, 215, 0, 0.1); }
        .mohaa-premium-table tr:hover td { background: rgba(255,255,255,0.05); color: #fff; }
        .mohaa-premium-table tr.top-rank:hover td { background: rgba(255, 215, 0, 0.1); }
        .rank-badge { font-size: 1.5em; display: inline-block; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
        .rank-num { font-weight: bold; color: #546e7a; font-size: 1.1em; }
        .player-info { display: flex; align-items: center; gap: 12px; }
        .player-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 0.9em; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .player-name { color: #fff; text-decoration: none; font-weight: 600; font-size: 1.05em; transition: color 0.2s; }
        .player-name:hover { color: #3498db; }
        .active-sort { color: #3498db !important; border-bottom: 2px solid #3498db; padding-bottom: 4px; }
        .stat-col.sorted { background: rgba(52, 152, 219, 0.05); font-weight: bold; color: #fff; }
        .stat-positive { color: #00e676; }
        .stat-negative { color: #e57373; }
        .stat-highlight { color: #fff; font-weight: bold; }
        .empty-state { text-align: center; padding: 60px; color: #7f8c8d; }
        
        .mohaa-loading { opacity: 0.5; pointer-events: none; transition: opacity 0.2s; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div id="mohaa-leaderboard-dynamic">
    ';
    
    // Stat Descriptions
    $statInfo = [
        'kills' => ['title' => 'Kills', 'desc' => 'Total enemies eliminated in combat.', 'icon' => '🗡️'],
        'deaths' => ['title' => 'Deaths', 'desc' => 'Times eliminated by enemy fire or mishaps.', 'icon' => '🪦'],
        'kd' => ['title' => 'K/D Ratio', 'desc' => 'Kill-to-Death ratio. The ultimate measure of efficiency.', 'icon' => '⚖️'],
        'headshots' => ['title' => 'Headshots', 'desc' => 'Precision kills resulting in instant death.', 'icon' => '🤯'],
        'accuracy' => ['title' => 'Accuracy', 'desc' => 'Percentage of shots that hit a target.', 'icon' => '🎯'],
        'shots_fired' => ['title' => 'Trigger Happy', 'desc' => 'Total ammunition expended. Spray and pray?', 'icon' => '💥'],
        'damage' => ['title' => 'Damage Dealer', 'desc' => 'Total damage inflicted on opponents.', 'icon' => '🩸'],
        'bash_kills' => ['title' => 'Executioner', 'desc' => 'Humiliating kills using pistol whips or rifle butts.', 'icon' => '🔨'],
        'grenade_kills' => ['title' => 'Grenadier', 'desc' => 'Explosive kills with hand grenades.', 'icon' => '💣'],
        'roadkills' => ['title' => 'Road Rage', 'desc' => 'Enemies run over by vehicles.', 'icon' => '🚗'],
        'telefrags' => ['title' => 'Telefrags', 'desc' => 'Occupying the same space as an enemy.', 'icon' => '🌌'],
        'crushed' => ['title' => 'Crushed', 'desc' => 'Squashed by world objects or elevators.', 'icon' => '🥞'],
        'teamkills' => ['title' => 'Betrayals', 'desc' => 'Teammates eliminated. Friendly fire isn\'t friendly.', 'icon' => '🔪'],
        'suicides' => ['title' => 'Suicides', 'desc' => 'Self-inflicted eliminations.', 'icon' => '💀'],
        'reloads' => ['title' => 'Reloader', 'desc' => 'Times a weapon clip was swapped.', 'icon' => '🔄'],
        'weapon_swaps' => ['title' => 'Fickle', 'desc' => 'Times weapons were switched during combat.', 'icon' => '🔀'],
        'no_ammo' => ['title' => 'Empty Clip', 'desc' => 'Times caught clicking with an empty gun.', 'icon' => '⛽'],
        'looter' => ['title' => 'Looter', 'desc' => 'Weapons picked up from the ground.', 'icon' => '🎒'],
        'distance' => ['title' => 'Marathon Man', 'desc' => 'Total distance travelled on foot.', 'icon' => '🏃'],
        'sprinted' => ['title' => 'Sprinter', 'desc' => 'Distance covered while sprinting.', 'icon' => '⚡'],
        'swam' => ['title' => 'Swimmer', 'desc' => 'Distance covered in water.', 'icon' => '🏊'],
        'driven' => ['title' => 'Driver', 'desc' => 'Distance covered in vehicles.', 'icon' => '🚙'],
        'jumps' => ['title' => 'Bunny Hopper', 'desc' => 'Total number of jumps performed.', 'icon' => '🐇'],
        'crouch_time' => ['title' => 'Tactical Crouch', 'desc' => 'Time spent moving in a crouched position.', 'icon' => '🦵'],
        'prone_time' => ['title' => 'Camper', 'desc' => 'Time spent laying on the ground.', 'icon' => '⛺'],
        'ladders' => ['title' => 'Mountaineer', 'desc' => 'Time spent climbing ladders.', 'icon' => '🧗'],
        'health_picked' => ['title' => 'Glutton', 'desc' => 'Health packs consumed.', 'icon' => '🍗'],
        'ammo_picked' => ['title' => 'Hoarder', 'desc' => 'Ammo crates collected.', 'icon' => '📦'],
        'armor_picked' => ['title' => 'Tank', 'desc' => 'Jacket Armor collected.', 'icon' => '🛡️'],
        'items_picked' => ['title' => 'Scavenger', 'desc' => 'Total items picked up.', 'icon' => '🗑️'],
        'wins' => ['title' => 'Wins', 'desc' => 'Total Games Won (FFA + Team).', 'icon' => '🏆'],
        'team_wins' => ['title' => 'Team Wins', 'desc' => 'Games won as part of a team (Objective/TDM).', 'icon' => '🚩'],
        'ffa_wins' => ['title' => 'FFA Wins', 'desc' => 'Deathmatch games won solo.', 'icon' => '⚔️'],
        'losses' => ['title' => 'Losses', 'desc' => 'Matches lost or not placed 1st.', 'icon' => '☠️'],
        'objectives_done' => ['title' => 'Objective Master', 'desc' => 'Mission objectives completed.', 'icon' => '🎯'],
        'rounds_played' => ['title' => 'Veteran', 'desc' => 'Total rounds played in round-based modes.', 'icon' => '⏳'],
        'games_finished' => ['title' => 'Ironman', 'desc' => 'Full matches completed from start to finish.', 'icon' => '🎮'],
    ];

    $info = $statInfo[$current_stat] ?? ['title' => ucfirst($current_stat), 'desc' => 'Global rankings for this metric.', 'icon' => '📊'];

    // Hero Section
    echo '
    <div class="mohaa-hero-stat" style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 100%); margin-bottom: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
        <div style="font-size: 4em; margin-bottom: 10px; filter: drop-shadow(0 0 10px rgba(255,255,255,0.2));">', $info['icon'], '</div>
        <h1 style="font-size: 2.5em; margin: 0; color: #fff; text-transform: uppercase; letter-spacing: 2px;">', $info['title'], '</h1>
        <p style="font-size: 1.2em; color: #aab7c4; max-width: 600px; margin: 10px auto 0;">', $info['desc'], '</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div class="windowbg mohaa-lb-wrap" style="padding: 20px;">
    
    <!-- Chart Section -->
    <div class="mohaa-chart-container" style="position: relative; height:350px; width:100%; margin-bottom: 30px;">
        <canvas id="leaderboardChart"></canvas>
    </div>';

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
    // Premium Leaderboard Table
    echo '
    <div style="overflow-x: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <table class="mohaa-premium-table">
            <thead>
                <tr>
                    <th class="rank-col">Rank</th>
                    <th class="player-col">Player</th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=kills;period=', $current_period, '" class="', ($current_stat == 'kills' ? 'active-sort' : ''), '">Kills</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=deaths;period=', $current_period, '" class="', ($current_stat == 'deaths' ? 'active-sort' : ''), '">Deaths</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=kd;period=', $current_period, '" class="', ($current_stat == 'kd' ? 'active-sort' : ''), '">K/D</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=headshots;period=', $current_period, '" class="', ($current_stat == 'headshots' ? 'active-sort' : ''), '">HS</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=accuracy;period=', $current_period, '" class="', ($current_stat == 'accuracy' ? 'active-sort' : ''), '">Acc%</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=wins;period=', $current_period, '" class="', ($current_stat == 'wins' ? 'active-sort' : ''), '">Wins</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=rounds;period=', $current_period, '" class="', ($current_stat == 'rounds' ? 'active-sort' : ''), '">Rounds</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=objectives;period=', $current_period, '" class="', ($current_stat == 'objectives' ? 'active-sort' : ''), '">Obj</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=distance;period=', $current_period, '" class="', ($current_stat == 'distance' ? 'active-sort' : ''), '">Dist</a></th>
                    <th class="stat-col"><a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=playtime;period=', $current_period, '" class="', ($current_stat == 'playtime' ? 'active-sort' : ''), '">Time</a></th>
                </tr>
            </thead>
            <tbody>';

    if (empty($leaderboard)) {
        echo '
            <tr>
                <td colspan="12" class="empty-state">
                    <div style="font-size: 3em; margin-bottom: 15px;">🎮</div>
                    <div style="font-size: 1.2em;">No data found for this period.</div>
                    <div style="opacity: 0.6;">Be the first to claim a spot!</div>
                </td>
            </tr>';
    } else {
        foreach ($leaderboard as $rank => $player) {
            $rankNum = $rank + 1;
            
            // Rank Badge Logic
            $rankDisplay = '<span class="rank-num">#' . $rankNum . '</span>';
            if ($rankNum === 1) $rankDisplay = '<span class="rank-badge gold">🥇</span>';
            if ($rankNum === 2) $rankDisplay = '<span class="rank-badge silver">🥈</span>';
            if ($rankNum === 3) $rankDisplay = '<span class="rank-badge bronze">🥉</span>';
            
            // Calculated Stats
            $kd = $player['deaths'] > 0 ? round($player['kills'] / $player['deaths'], 2) : $player['kills'];
            $kdColor = $kd >= 2.0 ? '#00e676' : ($kd >= 1.0 ? '#81c784' : '#e57373');
            $acc = round($player['accuracy'] ?? 0, 1);
            $dist = round(($player['distance_km'] ?? 0) / 1000, 1);
            $time = format_playtime($player['playtime_seconds'] ?? 0);
            
            // Row Highlight (Alternating is handled by CSS, but Top 3 get special glow)
            $rowClass = $rankNum <= 3 ? 'top-rank' : '';
            
            echo '
            <tr class="', $rowClass, '">
                <td class="rank-col">', $rankDisplay, '</td>
                <td class="player-col">
                    <div class="player-info">
                        <div class="player-avatar" style="background-color: ', ($rankNum <= 3 ? '#ffd700' : '#546e7a'), ';">
                            ', strtoupper(substr($player['name'], 0, 1)), '
                        </div>
                        <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '" class="player-name">
                            ', htmlspecialchars($player['name']), '
                        </a>
                    </div>
                </td>
                <td class="stat-col ', ($current_stat == 'kills' ? 'sorted' : ''), '">', number_format($player['kills']), '</td>
                <td class="stat-col ', ($current_stat == 'deaths' ? 'sorted' : ''), '">', number_format($player['deaths']), '</td>
                <td class="stat-col ', ($current_stat == 'kd' ? 'sorted' : ''), '" style="color: ', $kdColor, ';">', $kd, '</td>
                <td class="stat-col ', ($current_stat == 'headshots' ? 'sorted' : ''), '">', number_format($player['headshots']), '</td>
                <td class="stat-col ', ($current_stat == 'accuracy' ? 'sorted' : ''), '">', $acc, '%</td>
                <td class="stat-col ', ($current_stat == 'wins' ? 'sorted' : ''), '">', number_format($player['wins'] ?? 0), '</td>
                <td class="stat-col ', ($current_stat == 'rounds' ? 'sorted' : ''), '">', number_format($player['rounds'] ?? 0), '</td>
                <td class="stat-col ', ($current_stat == 'objectives' ? 'sorted' : ''), '">', number_format($player['objectives'] ?? 0), '</td>
                <td class="stat-col ', ($current_stat == 'distance' ? 'sorted' : ''), '">', $dist, ' km</td>
                <td class="stat-col ', ($current_stat == 'playtime' ? 'sorted' : ''), '">', $time, '</td>
            </tr>';
        }
    }

    echo '
        </tbody>
    </table>
    </div>';

    // Pagination
    if (!empty($context['page_index'])) {
        echo '
        <div class="pagesection">
            <div class="pagelinks">', $context['page_index'], '</div>
        </div>';
    }
    
    // Prepare Chart Data
    $chartLabels = [];
    $chartData = [];
    $topCount = 0;
    foreach ($leaderboard as $player) {
        if ($topCount >= 10) break;
        $chartLabels[] = $player['name'];
        // Handle different stat types (percentages, raw numbers)
        $val = $player[$current_stat] ?? 0;
        // Clean up value if needed
        $chartData[] = $val;
        $topCount++;
    }
    
    echo '
    <script>
        (function() {
            var initChart = function() {
                var ctx = document.getElementById("leaderboardChart").getContext("2d");
                
                // Destroy existing if any
                if (window.myLeaderboardChart) {
                    window.myLeaderboardChart.destroy();
                }
            
            // Gradient for bars
            var gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, "rgba(52, 152, 219, 0.8)");
            gradient.addColorStop(1, "rgba(41, 128, 185, 0.2)");

            var chart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ', json_encode($chartLabels), ',
                    datasets: [{
                        label: "', ucfirst($current_stat), '",
                        data: ', json_encode($chartData), ',
                        backgroundColor: gradient,
                        borderColor: "#3498db",
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: "#5dade2"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: "Top 10 Performers - ', ucfirst($current_stat), '",
                            color: "#bdc3c7",
                            font: { size: 16 }
                        },
                        tooltip: {
                            mode: "index",
                            intersect: false,
                            backgroundColor: "rgba(0,0,0,0.8)",
                            titleColor: "#f39c12",
                            bodyFont: { size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: "rgba(255,255,255,0.05)" },
                            ticks: { color: "#95a5a6" }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: "#ecf0f1", font: { weight: "bold" } }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: "easeOutQuart"
                    }
                }
            });
            
            // Save reference
            window.myLeaderboardChart = chart;
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initChart);
            } else {
                initChart();
            }
        })();
    </script>';
    
    // Close Dynamic Container
    echo '</div>';
    
    // PJAX Navigation Script
    echo '
    <script>
    document.addEventListener("click", function(e) {
        var link = e.target.closest("a");
        if (!link) return;
        
        var wrapper = document.getElementById("mohaa-leaderboard-dynamic");
        if (!wrapper) return;
        
        if (wrapper.contains(link) && link.href.includes("action=mohaastats") && !link.target) {
            e.preventDefault();
            var url = link.href;
            
            wrapper.classList.add("mohaa-loading");
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, "text/html");
                    var newContent = doc.getElementById("mohaa-leaderboard-dynamic");
                    
                    if (newContent) {
                        wrapper.innerHTML = newContent.innerHTML;
                        wrapper.classList.remove("mohaa-loading");
                        history.pushState(null, "", url);
                        
                        // Execute Scripts
                        var scripts = wrapper.getElementsByTagName("script");
                        for (var i = 0; i < scripts.length; i++) {
                            eval(scripts[i].innerText);
                        }
                    } else {
                        window.location.href = url; // Fallback
                    }
                })
                .catch(err => {
                    console.error("PJAX Error:", err);
                    window.location.href = url; // Fallback
                });
        }
    });
    
    window.addEventListener("popstate", function() {
        location.reload(); 
    });
    </script>';
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
/**
 * Competitive Leaderboard Dashboard
 */
function template_mohaa_stats_dashboard()
{
    global $context, $txt, $scripturl, $settings;

    // Use the data structure from the controller
    $cardsData = $context['mohaa_dashboard_cards'] ?? [];

    // Definition of all 40 widgets
    $widgets = [
        // A. Lethality
        'kills' => ['label' => 'Kills', 'icon' => '🗡️'],
        'deaths' => ['label' => 'Deaths', 'icon' => '🪦'],
        'kd' => ['label' => 'K/D Ratio', 'icon' => '⚖️'],
        'headshots' => ['label' => 'Headshots', 'icon' => '🤯'],
        'accuracy' => ['label' => 'Accuracy', 'icon' => '🎯'],
        'shots_fired' => ['label' => 'Trigger Happy', 'icon' => '💥'],
        'damage' => ['label' => 'Damage Dealer', 'icon' => '🩸'],
        'bash_kills' => ['label' => 'Executioner', 'icon' => '🔨'],
        'grenade_kills' => ['label' => 'Grenadier', 'icon' => '💣'],
        'roadkills' => ['label' => 'Road Rage', 'icon' => '🚗'],
        'telefrags' => ['label' => 'Telefrags', 'icon' => '🌌'],
        'crushed' => ['label' => 'Crushed', 'icon' => '🥞'],
        'teamkills' => ['label' => 'Betrayals', 'icon' => '🔪'],
        'suicides' => ['label' => 'Suicides', 'icon' => '💀'],

        // B. Weapon
        'reloads' => ['label' => 'Reloader', 'icon' => '🔄'],
        'weapon_swaps' => ['label' => 'Fickle', 'icon' => '🔀'],
        'no_ammo' => ['label' => 'Empty Clip', 'icon' => '⛽'],
        'looter' => ['label' => 'Looter', 'icon' => '🎒'],

        // C. Movement
        'distance' => ['label' => 'Marathon Man', 'icon' => '🏃'],
        'sprinted' => ['label' => 'Sprinter', 'icon' => '⚡'],
        'swam' => ['label' => 'Swimmer', 'icon' => '🏊'],
        'driven' => ['label' => 'Driver', 'icon' => '🚙'],
        'jumps' => ['label' => 'Bunny Hopper', 'icon' => '🐇'],
        'crouch_time' => ['label' => 'Tactical Crouch', 'icon' => '🦵'],
        'prone_time' => ['label' => 'Camper', 'icon' => '⛺'],
        'ladders' => ['label' => 'Mountaineer', 'icon' => '🧗'],

        // D. Survival
        'health_picked' => ['label' => 'Glutton', 'icon' => '🍗'],
        'ammo_picked' => ['label' => 'Hoarder', 'icon' => '📦'],
        'armor_picked' => ['label' => 'Tank', 'icon' => '🛡️'],
        'items_picked' => ['label' => 'Scavenger', 'icon' => '🗑️'],

        // E. Objectives
        'wins' => ['label' => 'Wins', 'icon' => '🏆'],
        'team_wins' => ['label' => 'Team Wins', 'icon' => '🚩'],
        'ffa_wins' => ['label' => 'FFA Wins', 'icon' => '⚔️'],
        'losses' => ['label' => 'Losses', 'icon' => '☠️'],
        'objectives_done' => ['label' => 'Objective Master', 'icon' => '🎯'],
        'rounds_played' => ['label' => 'Veteran', 'icon' => '⏳'],
        'games_finished' => ['label' => 'Ironman', 'icon' => '🎮'],

        // F. Vehicles
        'vehicle_enter' => ['label' => 'Pilot', 'icon' => '✈️'],
        'turret_enter' => ['label' => 'Gunner', 'icon' => '🔫'],
        'vehicle_kills' => ['label' => 'Saboteur', 'icon' => '🧨'],

        // G. Social
        'chat_msgs' => ['label' => 'Chatterbox', 'icon' => '💬'],
        'spectating' => ['label' => 'Spectator', 'icon' => '👁️'],
        'doors_opened' => ['label' => 'Door Monitor', 'icon' => '🚪'],
    ];

    echo '
    <div class="mohaa-dashboard">

        <!-- War Room Header -->
        <div class="war-room-header">
            <div class="header-title">
                <h1>🏆 TOTAL DOMINATION</h1>
                <span class="header-subtitle">Performance Analysis & Global Records</span>
            </div>
            <div class="header-actions">
                <a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=kills" class="button">📋 View Detailed Table</a>
            </div>
        </div>
        
        <!-- Ensure CSS is loaded -->
        <link rel="stylesheet" href="', $settings['theme_url'], '/css/mohaa_dashboard.css?v=2" />
        
        <div class="mohaa-stats-grid">';

    foreach ($widgets as $key => $meta) {
        $top3 = $cardsData[$key] ?? [];
        
        // Render Card
        echo '
        <div class="mohaa-stat-card clickable" onclick="window.location.href=\'', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, '\'" style="cursor: pointer;">
            <div class="card-header">
                <span class="card-icon">', $meta['icon'], '</span>
                <span class="card-title">', $meta['label'], '</span>
            </div>
            <ul class="top-list">';

        if (empty($top3)) {
            echo '<li class="top-entry"><span class="name" style="color:inherit; opacity:0.5; font-style:italic;">No Data</span></li>';
        } else {
            foreach ($top3 as $idx => $player) {
                $rank = $idx + 1;
                echo '
                <li class="top-entry rank-', $rank, '">
                    <span class="rank">#', $rank, '</span>
                    <span class="name">
                        <a href="', $scripturl, '?action=mohaastats;sa=player;id=', $player['id'], '">', htmlspecialchars($player['name']), '</a>
                    </span>
                    <span class="value">', $player['value'], '</span>
                </li>';
            }
        }
        
        echo '
            </ul>
             <a href="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=', $key, '" class="card-footer">
                View Leaderboard
            </a>
        </div>';
    }

    echo '
        </div>
    </div>';
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
                    <span class="name">
                        <a href="' . $scripturl . '?action=mohaastats;sa=player;id=' . ($entry['id'] ?? 0) . '" onclick="event.stopPropagation();">
                            ' . htmlspecialchars($entry['name']) . '
                        </a>
                    </span>
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
