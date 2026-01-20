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
        .mohaa-hero-stat { 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            padding: 40px; 
            background: linear-gradient(135deg, #1a252f 0%, #2c3e50 100%); 
            margin-bottom: 30px; 
            border-radius: 12px; 
            color: #ffffff; 
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.05);
            text-align: left;
        }
        .mohaa-hero-stat .stat-icon {
            font-size: 3.5em; 
            line-height: 1;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .mohaa-hero-stat h1 { 
            color: #ffffff; 
            text-shadow: 0 2px 4px rgba(0,0,0,0.3); 
            margin: 0 0 5px 0; 
            font-size: 2.2em;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .mohaa-hero-stat p { 
            color: #bdc3c7; 
            font-size: 1.1em; 
            margin: 0; 
            max-width: 600px;
        }
        
        .ag-theme-alpine { --ag-foreground-color: #2c3e50; --ag-header-foreground-color: #fff; --ag-header-background-color: #2c3e50; --ag-row-hover-color: rgba(52, 152, 219, 0.1); --ag-selected-row-background-color: rgba(52, 152, 219, 0.2); }
        .ag-theme-alpine .ag-header-cell { font-family: "Inter", sans-serif; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; }
        .ag-theme-alpine .ag-cell { font-family: "Inter", sans-serif; display: flex; align-items: center; justify-content: center; }
        .player-cell { justify-content: flex-start !important; }
        
        .rank-badge { font-size: 1.4em; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.2)); }
        .player-avatar-small { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #34495e; color: #fff; font-size: 10px; margin-right: 8px; font-weight: bold; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/styles/ag-theme-alpine.css">
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.0/dist/ag-grid-community.min.js"></script>
    
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
        'objectives' => ['title' => 'Objective Master', 'desc' => 'Mission objectives completed.', 'icon' => '🎯'],
        'rounds' => ['title' => 'Veteran', 'desc' => 'Total rounds played in round-based modes.', 'icon' => '⏳'],
        'playtime' => ['title' => 'Time Sink', 'desc' => 'Total time spent on the server.', 'icon' => '⏱️'],
        'games' => ['title' => 'Ironman', 'desc' => 'Full matches completed from start to finish.', 'icon' => '🎮'],
    ];

    $info = $statInfo[$current_stat] ?? ['title' => ucfirst($current_stat), 'desc' => 'Global rankings for this metric.', 'icon' => '📊'];

    // Hero Section
    echo '
        <div style="text-align: left;">
            <h1 style="font-size: 2em; text-transform: uppercase; letter-spacing: 2px; margin: 0;">', $info['title'], '</h1>
            <p style="margin: 0; opacity: 0.9; font-size: 0.9em;">', $info['desc'], '</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <div class="windowbg mohaa-lb-wrap" style="padding: 20px;">';
    




    // PERIOD Filters
    echo '
        <div class="mohaa-filter-section" style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <strong style="margin-right: 5px; color: #7f8c8d; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px;">📅 Period:</strong>';
    $periods = ['all' => 'All Time', 'month' => 'This Month', 'week' => 'This Week', 'day' => 'Today'];
    foreach ($periods as $key => $label) {
        $class = ($current_period === $key) ? 'active' : 'inactive';
        $style = ($current_period === $key) 
            ? 'background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 0.9em; font-weight: bold; box-shadow: 0 2px 4px rgba(52,152,219,0.3);' 
            : 'background: #ecf0f1; color: #7f8c8d; padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 0.9em; transition: all 0.2s;';
            
        echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $current_stat, ';period=', $key, '" class="mohaa-chip" style="', $style, '">', $label, '</a>';
    }
    echo '</div>
    </div>';

    // LEADERBOARD TABLE - AG GRID
    if (empty($leaderboard)) {
        echo '
        <div class="windowbg" style="text-align: center; padding: 60px 20px; border-radius: 8px; margin-top: 20px;">
            <div style="font-size: 4em; margin-bottom: 15px; opacity: 0.5;">📉</div>
            <h3 style="margin: 0; color: #7f8c8d;">No Data Available</h3>
            <p style="color: #95a5a6;">There are no stats recorded for <strong>', $info['title'], '</strong> in this period.</p>
        </div>';
    } else {
        echo '
        <div id="myGrid" class="ag-theme-alpine" style="height: 600px; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 20px;"></div>
        
        <script>
            (function() {
                var rowData = ', json_encode($leaderboard), ';
            var scriptUrl = "', $scripturl, '";
            
            var rankRenderer = function(params) {
                var rank = params.node.rowIndex + 1;
                if (rank === 1) return "<span class=\"rank-badge\">🥇</span>";
                if (rank === 2) return "<span class=\"rank-badge\">🥈</span>";
                if (rank === 3) return "<span class=\"rank-badge\">🥉</span>";
                return "#" + rank;
            };
            
            var playerRenderer = function(params) {
                if (!params.value) return "";
                var name = params.data.name;
                var id = params.data.id;
                var initial = name.charAt(0).toUpperCase();
                var color = (params.node.rowIndex < 3) ? "#f1c40f" : "#34495e";
                
                return `<div class="player-info" style="display:flex;align-items:center;">
                    <div class="player-avatar-small" style="background:${color}">${initial}</div>
                    <a href="${scriptUrl}?action=mohaastats;sa=player;id=${id}" style="font-weight:600;text-decoration:none;color:#2980b9;">${name}</a>
                </div>`;
            };
            
            var kdGetter = function(params) {
                var kills = parseInt(params.data.kills || 0);
                var deaths = parseInt(params.data.deaths || 0);
                return deaths > 0 ? (kills / deaths).toFixed(2) : kills;
            };
            
            var kdStyle = function(params) {
                var val = parseFloat(params.value);
                if (val >= 2.0) return { color: "#27ae60", fontWeight: "bold" };
                if (val >= 1.0) return { color: "#2980b9" };
                return { color: "#e74c3c" };
            };
            
            var gridOptions = {
                rowData: rowData,
                columnDefs: [
                    { headerName: "#", width: 70, cellRenderer: rankRenderer, sortable: false, pinned: "left" },
                    { field: "name", headerName: "Player", minWidth: 200, cellRenderer: playerRenderer, cellClass: "player-cell", pinned: "left" },
                    { field: "kills", headerName: "Kills", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "deaths", headerName: "Deaths", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { headerName: "K/D", width: 100, valueGetter: kdGetter, cellStyle: kdStyle, type: "numericColumn", sortable: true, comparator: (a,b) => parseFloat(a)-parseFloat(b) },
                    { field: "headshots", headerName: "HS", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "accuracy", headerName: "Acc %", width: 90, valueFormatter: p => p.value + "%", type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "wins", headerName: "Wins", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "rounds", headerName: "Rounds", width: 100, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "objectives", headerName: "Obj", width: 90, type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "distance_km", headerName: "Dist (km)", width: 120, valueFormatter: p => (p.value/1000).toFixed(1), type: "numericColumn", sortable: true, comparator: (a,b) => a-b },
                    { field: "playtime_seconds", headerName: "Time", width: 120, valueFormatter: p => Math.floor(p.value/60) + "m", type: "numericColumn", sortable: true, comparator: (a,b) => a-b }
                ],
                defaultColDef: {
                    resizable: true,
                    filter: true,
                    flex: 1,
                    minWidth: 100
                },
                pagination: true,
                paginationPageSize: 20,
                animateRows: true,
                domLayout: "autoHeight"
            };
            
            var eGridDiv = document.querySelector("#myGrid");
            // Check for modern API or legacy API just in case
            if (agGrid.createGrid) {
                agGrid.createGrid(eGridDiv, gridOptions);
            } else {
                new agGrid.Grid(eGridDiv, gridOptions);
            }
                
                // Expose for PJAX to re-init if needed? 
                // Actually PJAX replaces the whole container, so re-executing this script works.
            })();
        </script>';
    }

    // STAT CATEGORY FILTERS (Moved to bottom)
    echo '
    <div style="margin-top: 40px;">
    
        <!-- Chart Section (Moved Bottom) -->
        <div class="mohaa-chart-container" style="position: relative; height:300px; width:100%; margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; padding: 10px; background: #fff;">
            <canvas id="leaderboardChart"></canvas>
        </div>

        <div style="padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 1.2em; text-transform: uppercase; letter-spacing: 1px;">📊 Explore Other Metrics</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">';
        
        $statGroups = [
            '⚔️ Combat' => ['kills' => 'Kills', 'deaths' => 'Deaths', 'kd' => 'K/D Ratio', 'headshots' => 'Headshots', 'accuracy' => 'Accuracy', 'damage' => 'Damage Dealt'],
            '💀 Special' => ['suicides' => 'Suicides', 'teamkills' => 'Team Kills', 'roadkills' => 'Roadkills', 'bash_kills' => 'Bash Kills', 'grenades' => 'Grenade Kills'],
            '🎮 Game' => ['wins' => 'Wins', 'rounds' => 'Rounds Played', 'objectives' => 'Objectives', 'playtime' => 'Playtime'],
            '🏃 Move' => ['distance' => 'Distance Run', 'jumps' => 'Jumps'],
        ];

        foreach ($statGroups as $groupName => $stats) {
            echo '
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.05);">
                <strong style="display: block; margin-bottom: 10px; color: #34495e; font-size: 0.9em; text-transform: uppercase;">', $groupName, '</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">';
                foreach ($stats as $key => $label) {
                    $isActive = ($current_stat === $key);
                    $style = $isActive 
                        ? 'background: #2c3e50; color: white; padding: 4px 10px; border-radius: 15px; text-decoration: none; font-size: 0.85em; font-weight: bold; box-shadow: 0 2px 4px rgba(44,62,80,0.3);' 
                        : 'background: white; color: #7f8c8d; padding: 4px 10px; border-radius: 15px; text-decoration: none; font-size: 0.85em; border: 1px solid #dfe6e9; transition: all 0.2s;';
                    
                    echo '<a href="', $scripturl, '?action=mohaastats;sa=leaderboards;stat=', $key, ';period=', $current_period, '" style="', $style, '">', $label, '</a>';
                }
            echo '
                </div>
            </div>';
        }

    echo '
        </div>
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
                            color: "#2c3e50",
                            font: { size: 16 }
                        },
                        tooltip: {
                            mode: "index",
                            intersect: false,
                            backgroundColor: "rgba(44, 62, 80, 0.9)",
                            titleColor: "#ecf0f1",
                            bodyFont: { size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: "rgba(0,0,0,0.05)" },
                            ticks: { color: "#7f8c8d" }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: "#34495e", font: { weight: "bold" } }
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
 * Map leaderboard template - Comprehensive Map Statistics Dashboard
 */
function template_mohaa_stats_map_leaderboard()
{
    global $context, $scripturl, $txt;

    $selectedMap = $context['mohaa_map'] ?? '';
    $maps = $context['mohaa_maps_list'] ?? [];
    $leaderboardData = $context['mohaa_map_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['leaderboard'] ?? [];
    $mapData = $context['mohaa_map_data'] ?? [];
    
    // Calculate aggregate stats from maps list
    $totalMatches = 0;
    $totalKills = 0;
    $topMaps = [];
    foreach ($maps as $m) {
        $totalMatches += ($m['total_matches'] ?? 0);
        $totalKills += ($m['total_kills'] ?? 0);
        $topMaps[] = $m;
    }
    usort($topMaps, fn($a, $b) => ($b['total_matches'] ?? 0) <=> ($a['total_matches'] ?? 0));
    $topMaps = array_slice($topMaps, 0, 8);
    
    // CSS
    echo '
    <style>
    .mohaa-maps-dashboard { display: flex; flex-direction: column; gap: 20px; }
    .mohaa-maps-header { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 10px; }
    .mohaa-maps-stat-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px; padding: 20px; text-align: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .mohaa-maps-stat-card .stat-icon { font-size: 2rem; margin-bottom: 10px; }
    .mohaa-maps-stat-card .stat-value { font-size: 1.8rem; font-weight: bold; color: #fff; }
    .mohaa-maps-stat-card .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
    .mohaa-maps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .mohaa-map-card {
        background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%);
        border-radius: 12px; overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
    }
    .mohaa-map-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
    .mohaa-map-card .map-image {
        width: 100%; height: 140px; object-fit: cover;
        background: #2a2a3e; display: flex; align-items: center; justify-content: center;
    }
    .mohaa-map-card .map-image img { width: 100%; height: 100%; object-fit: cover; }
    .mohaa-map-card .map-placeholder { color: #666; font-size: 3rem; }
    .mohaa-map-card .map-info { padding: 15px; }
    .mohaa-map-card .map-name { font-size: 1.1rem; font-weight: bold; color: #fff; margin-bottom: 8px; }
    .mohaa-map-card .map-stats { display: flex; justify-content: space-between; gap: 10px; }
    .mohaa-map-card .map-stat { text-align: center; }
    .mohaa-map-card .map-stat-value { font-weight: bold; color: #4fc3f7; }
    .mohaa-map-card .map-stat-label { font-size: 0.75rem; color: #888; }
    .mohaa-map-card.selected { border-color: #4fc3f7; box-shadow: 0 0 15px rgba(79,195,247,0.3); }
    .mohaa-charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .mohaa-chart-container { background: #1a1a2e; border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); }
    .mohaa-chart-container h4 { margin: 0 0 15px 0; color: #fff; font-size: 1rem; }
    .mohaa-map-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .mohaa-heatmap-section { background: #1a1a2e; border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); }
    .mohaa-heatmap-section img { max-width: 100%; border-radius: 8px; }
    .mohaa-map-leaderboard { background: #1a1a2e; border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); }
    .mohaa-map-leaderboard h4 { margin: 0 0 15px 0; color: #fff; }
    .mohaa-map-leaderboard table { width: 100%; }
    .mohaa-map-leaderboard th, .mohaa-map-leaderboard td { padding: 10px; text-align: left; }
    .mohaa-map-leaderboard tr:hover { background: rgba(255,255,255,0.05); }
    .mohaa-filters-bar { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .mohaa-filter-select { padding: 8px 15px; border-radius: 6px; background: #2a2a3e; color: #fff; border: 1px solid #444; }
    @media (max-width: 768px) {
        .mohaa-maps-header { grid-template-columns: repeat(2, 1fr); }
        .mohaa-charts-row, .mohaa-map-detail { grid-template-columns: 1fr; }
    }
    </style>';
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">🗺️ ', $txt['mohaa_map_stats'] ?? 'Map Statistics', '</h3>
    </div>
    
    <div class="windowbg mohaa-maps-dashboard">';
    
    // Aggregate Stats Header
    echo '
        <div class="mohaa-maps-header">
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-value">', count($maps), '</div>
                <div class="stat-label">Total Maps</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">🎮</div>
                <div class="stat-value">', number_format($totalMatches), '</div>
                <div class="stat-label">Total Matches</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">💀</div>
                <div class="stat-value">', number_format($totalKills), '</div>
                <div class="stat-label">Total Kills</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value">', !empty($topMaps) ? ($topMaps[0]['display_name'] ?? $topMaps[0]['name'] ?? 'N/A') : 'N/A', '</div>
                <div class="stat-label">Most Popular Map</div>
            </div>
        </div>';
    
    // Charts Row - Map Popularity
    echo '
        <div class="mohaa-charts-row">
            <div class="mohaa-chart-container">
                <h4>📊 Map Popularity (By Matches)</h4>
                <div id="mapPopularityChart" style="height: 300px;"></div>
            </div>
            <div class="mohaa-chart-container">
                <h4>💀 Map Lethality (Kills Per Map)</h4>
                <div id="mapKillsChart" style="height: 300px;"></div>
            </div>
        </div>';
    
    // Map Filter / Search
    echo '
        <div class="mohaa-filters-bar">
            <strong>Select Map:</strong>
            <form action="', $scripturl, '?action=mohaastats;sa=maps" method="get" style="display: inline-flex; gap: 10px;">
                <input type="hidden" name="action" value="mohaastats" />
                <input type="hidden" name="sa" value="maps" />
                <select name="map" class="mohaa-filter-select" onchange="this.form.submit()">
                    <option value="">-- All Maps Overview --</option>';
    
    foreach ($maps as $m) {
        $mapName = $m['name'] ?? '';
        $displayName = $m['display_name'] ?? $mapName;
        $selected = ($mapName === $selectedMap) ? ' selected' : '';
        echo '
                    <option value="', htmlspecialchars($mapName), '"', $selected, '>', htmlspecialchars($displayName), '</option>';
    }
    
    echo '
                </select>
            </form>
        </div>';
    
    // If a specific map is selected, show detailed view
    if (!empty($selectedMap) && !empty($mapData)) {
        $displayName = $mapData['display_name'] ?? $selectedMap;
        
        echo '
        <div class="cat_bar" style="margin-top: 10px;">
            <h3 class="catbg">📍 ', htmlspecialchars($displayName), ' - Detailed Statistics</h3>
        </div>
        
        <div class="mohaa-maps-header" style="grid-template-columns: repeat(4, 1fr);">
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">🎮</div>
                <div class="stat-value">', number_format($mapData['total_matches'] ?? 0), '</div>
                <div class="stat-label">Matches Played</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">💀</div>
                <div class="stat-value">', number_format($mapData['total_kills'] ?? 0), '</div>
                <div class="stat-label">Total Kills</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value">', format_playtime($mapData['total_playtime'] ?? 0), '</div>
                <div class="stat-label">Total Playtime</div>
            </div>
            <div class="mohaa-maps-stat-card">
                <div class="stat-icon">⚔️</div>
                <div class="stat-value">', ($mapData['total_matches'] > 0) ? round($mapData['total_kills'] / $mapData['total_matches'], 1) : 0, '</div>
                <div class="stat-label">Avg Kills/Match</div>
            </div>
        </div>
        
        <div class="mohaa-map-detail">
            <div class="mohaa-heatmap-section">
                <h4>🔥 Kill Heatmap</h4>
                <div id="map-heatmap" style="position: relative; min-height: 300px; background: #0a0a15; border-radius: 8px; overflow: hidden;">
                    <img src="Themes/default/images/mohaastats/maps/', htmlspecialchars($selectedMap), '.jpg" 
                         alt="', htmlspecialchars($displayName), '" 
                         style="width: 100%; display: block;"
                         onerror="this.style.display=\'none\'; this.parentNode.innerHTML=\'<div style=padding:50px;text-align:center;color:#666>Map preview not available</div>\';">
                </div>
                <div style="margin-top: 10px; display: flex; gap: 10px;">
                    <button onclick="switchHeatmapType(\'kills\')" class="button" id="btn-kills" style="flex: 1;">💀 Kills</button>
                    <button onclick="switchHeatmapType(\'deaths\')" class="button" id="btn-deaths" style="flex: 1;">☠️ Deaths</button>
                </div>
            </div>
            
            <div class="mohaa-map-leaderboard">
                <h4>🏆 Top Players on ', htmlspecialchars($displayName), '</h4>
                <table class="table_grid">
                    <thead>
                        <tr class="title_bar">
                            <th style="width: 40px;">#</th>
                            <th>Player</th>
                            <th style="width: 80px;">Kills</th>
                            <th style="width: 80px;">Deaths</th>
                            <th style="width: 70px;">K/D</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (!empty($leaderboard)) {
            foreach (array_slice($leaderboard, 0, 15) as $rank => $player) {
                $kills = (int)($player['kills'] ?? 0);
                $deaths = (int)($player['deaths'] ?? 0);
                $kd = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
                $kdClass = $kd >= 2 ? 'color: #4caf50;' : ($kd >= 1 ? 'color: #ffc107;' : 'color: #f44336;');
                
                echo '
                        <tr class="windowbg">
                            <td><strong>', $rank + 1, '</strong></td>
                            <td>
                                <a href="', $scripturl, '?action=mohaastats;sa=player;id=', urlencode($player['id'] ?? ''), '">
                                    ', htmlspecialchars($player['name'] ?? 'Unknown'), '
                                </a>
                            </td>
                            <td>', number_format($kills), '</td>
                            <td>', number_format($deaths), '</td>
                            <td style="', $kdClass, ' font-weight: bold;">', $kd, '</td>
                        </tr>';
            }
        } else {
            echo '
                        <tr class="windowbg"><td colspan="5" style="text-align: center; color: #888;">No player data available</td></tr>';
        }
        
        echo '
                    </tbody>
                </table>
            </div>
        </div>';
    } else {
        // Show all maps grid when no specific map selected
        echo '
        <div class="cat_bar" style="margin-top: 10px;">
            <h3 class="catbg">🗺️ All Maps</h3>
        </div>
        
        <div class="mohaa-maps-grid">';
        
        foreach ($maps as $m) {
            $mapName = $m['name'] ?? '';
            $displayName = $m['display_name'] ?? $mapName;
            $matches = $m['total_matches'] ?? 0;
            $kills = $m['total_kills'] ?? 0;
            
            echo '
            <a href="', $scripturl, '?action=mohaastats;sa=maps;map=', urlencode($mapName), '" class="mohaa-map-card">
                <div class="map-image">
                    <img src="Themes/default/images/mohaastats/maps/', htmlspecialchars($mapName), '.jpg" 
                         alt="', htmlspecialchars($displayName), '"
                         onerror="this.style.display=\'none\'; this.parentNode.innerHTML=\'<div class=map-placeholder>🗺️</div>\';">
                </div>
                <div class="map-info">
                    <div class="map-name">', htmlspecialchars($displayName), '</div>
                    <div class="map-stats">
                        <div class="map-stat">
                            <div class="map-stat-value">', number_format($matches), '</div>
                            <div class="map-stat-label">Matches</div>
                        </div>
                        <div class="map-stat">
                            <div class="map-stat-value">', number_format($kills), '</div>
                            <div class="map-stat-label">Kills</div>
                        </div>
                        <div class="map-stat">
                            <div class="map-stat-value">', $matches > 0 ? round($kills / $matches) : 0, '</div>
                            <div class="map-stat-label">Avg K/M</div>
                        </div>
                    </div>
                </div>
            </a>';
        }
        
        if (empty($maps)) {
            echo '
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🗺️</div>
                <div>No map data available yet. Play some matches!</div>
            </div>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
    
    // Charts JavaScript
    $chartMaps = array_slice($topMaps, 0, 10);
    $mapLabels = array_map(fn($m) => $m['display_name'] ?? $m['name'] ?? 'Unknown', $chartMaps);
    $mapMatches = array_map(fn($m) => $m['total_matches'] ?? 0, $chartMaps);
    $mapKills = array_map(fn($m) => $m['total_kills'] ?? 0, $chartMaps);
    
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var mapLabels = ', json_encode($mapLabels), ';
        var mapMatches = ', json_encode($mapMatches), ';
        var mapKills = ', json_encode($mapKills), ';
        
        // Map Popularity Chart (Horizontal Bar)
        if (document.getElementById("mapPopularityChart") && mapLabels.length > 0) {
            new ApexCharts(document.getElementById("mapPopularityChart"), {
                series: [{ name: "Matches", data: mapMatches }],
                chart: { type: "bar", height: 300, background: "transparent", toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "70%" } },
                colors: ["#4fc3f7"],
                xaxis: { categories: mapLabels, labels: { style: { colors: "#888" } } },
                yaxis: { labels: { style: { colors: "#888" } } },
                grid: { borderColor: "#333" },
                dataLabels: { enabled: true, style: { colors: ["#fff"] } },
                theme: { mode: "dark" }
            }).render();
        }
        
        // Map Kills Chart (Donut)
        if (document.getElementById("mapKillsChart") && mapLabels.length > 0) {
            new ApexCharts(document.getElementById("mapKillsChart"), {
                series: mapKills,
                chart: { type: "donut", height: 300, background: "transparent" },
                labels: mapLabels,
                colors: ["#f44336", "#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#2196f3", "#00bcd4", "#009688", "#4caf50", "#8bc34a"],
                legend: { position: "right", labels: { colors: "#888" } },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: "60%", labels: { show: true, total: { show: true, label: "Total Kills", color: "#888", formatter: function(w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(); } } } } } },
                theme: { mode: "dark" }
            }).render();
        }
    });';
    
    // Heatmap JavaScript (only if map is selected)
    if (!empty($selectedMap) && !empty($mapData)) {
        $heatmapData = $mapData['heatmap_data'] ?? ['kills' => [], 'deaths' => []];
        echo '
    var heatmapData = ', json_encode($heatmapData), ';
    var currentHeatmapType = "kills";
    
    function switchHeatmapType(type) {
        currentHeatmapType = type;
        document.getElementById("btn-kills").classList.toggle("button_submit", type === "kills");
        document.getElementById("btn-deaths").classList.toggle("button_submit", type === "deaths");
        // Re-render heatmap with new data (placeholder - implement full heatmap rendering)
        console.log("Switching to", type, "heatmap with", (heatmapData[type] || []).length, "points");
    }';
    }
    
    echo '
    </script>';
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

// ============================================================================
// GAME TYPES TEMPLATE
// ============================================================================

function template_mohaa_stats_gametypes()
{
    global $context, $txt, $scripturl;
    
    $selectedGameType = $context['mohaa_gametype'] ?? '';
    $gameTypes = $context['mohaa_gametypes_list'] ?? [];
    $leaderboardData = $context['mohaa_gametype_leaderboard'] ?? [];
    $leaderboard = $leaderboardData['leaderboard'] ?? [];
    $gameTypeData = $context['mohaa_gametype_data'] ?? [];
    
    // Calculate aggregate stats
    $totalMatches = 0;
    $totalKills = 0;
    $totalPlayers = 0;
    foreach ($gameTypes as $gt) {
        $totalMatches += (int)($gt['total_matches'] ?? 0);
        $totalKills += (int)($gt['total_kills'] ?? 0);
        $totalPlayers += (int)($gt['unique_players'] ?? 0);
    }
    
    // Find most popular
    $mostPopular = '';
    $maxMatches = 0;
    foreach ($gameTypes as $gt) {
        if ((int)($gt['total_matches'] ?? 0) > $maxMatches) {
            $maxMatches = (int)($gt['total_matches'] ?? 0);
            $mostPopular = $gt['name'] ?? strtoupper($gt['id'] ?? '');
        }
    }
    
    // CSS
    echo '
    <style>
    .mohaa-gametypes-dashboard { display: flex; flex-direction: column; gap: 20px; }
    .mohaa-gametypes-header { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 10px; }
    .mohaa-gametypes-stat-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px; padding: 20px; text-align: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .mohaa-gametypes-stat-card .stat-icon { font-size: 2rem; margin-bottom: 10px; }
    .mohaa-gametypes-stat-card .stat-value { font-size: 1.8rem; font-weight: bold; color: #fff; }
    .mohaa-gametypes-stat-card .stat-label { color: #888; font-size: 0.85rem; margin-top: 5px; }
    .mohaa-gametypes-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .mohaa-gametypes-chart-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px; padding: 20px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .mohaa-gametypes-chart-card h4 { color: #fff; margin: 0 0 15px 0; }
    .mohaa-gametypes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .mohaa-gametype-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 12px; overflow: hidden; cursor: pointer;
        border: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .mohaa-gametype-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .mohaa-gametype-card-header { padding: 20px; text-align: center; background: rgba(0,0,0,0.2); }
    .mohaa-gametype-card-header .icon { font-size: 3rem; margin-bottom: 10px; }
    .mohaa-gametype-card-header .name { font-size: 1.3rem; font-weight: bold; color: #fff; }
    .mohaa-gametype-card-header .description { color: #888; font-size: 0.85rem; margin-top: 5px; }
    .mohaa-gametype-card-stats { display: grid; grid-template-columns: repeat(3, 1fr); padding: 15px; }
    .mohaa-gametype-card-stats .stat { text-align: center; }
    .mohaa-gametype-card-stats .stat-value { font-size: 1.2rem; font-weight: bold; color: #4fc3f7; }
    .mohaa-gametype-card-stats .stat-label { font-size: 0.75rem; color: #888; }
    .mohaa-gametype-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .mohaa-gametype-detail-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .mohaa-gametype-maps-list { max-height: 400px; overflow-y: auto; }
    .mohaa-select-form { margin: 15px 0; }
    .mohaa-select-form select { padding: 8px 15px; border-radius: 6px; border: 1px solid #444; background: #2a2a3e; color: #fff; }
    </style>';
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">🎮 Game Type Statistics</h3>
    </div>
    
    <div class="mohaa-gametypes-dashboard">';
    
    // Header stats
    echo '
        <div class="mohaa-gametypes-header">
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">🎮</div>
                <div class="stat-value">', count($gameTypes), '</div>
                <div class="stat-label">Game Types</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">⚔️</div>
                <div class="stat-value">', number_format($totalMatches), '</div>
                <div class="stat-label">Total Matches</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">💀</div>
                <div class="stat-value">', number_format($totalKills), '</div>
                <div class="stat-label">Total Kills</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value">', htmlspecialchars($mostPopular ?: 'N/A'), '</div>
                <div class="stat-label">Most Popular</div>
            </div>
        </div>';
    
    // Charts row
    echo '
        <div class="mohaa-gametypes-charts">
            <div class="mohaa-gametypes-chart-card">
                <h4>📊 Game Type Popularity (By Matches)</h4>
                <div id="gametypePopularityChart"></div>
            </div>
            <div class="mohaa-gametypes-chart-card">
                <h4>💀 Kill Distribution By Game Type</h4>
                <div id="gametypeKillsChart"></div>
            </div>
        </div>';
    
    // Game type selector
    echo '
        <div class="mohaa-select-form">
            <form method="get" action="', $scripturl, '">
                <input type="hidden" name="action" value="mohaastats">
                <input type="hidden" name="sa" value="gametypes">
                Select Game Type: 
                <select name="gametype" onchange="this.form.submit()">
                    <option value="">-- All Game Types Overview --</option>';
    
    foreach ($gameTypes as $gt) {
        $id = $gt['id'] ?? '';
        $name = $gt['name'] ?? strtoupper($id);
        $icon = $gt['icon'] ?? '🎮';
        $selected = ($selectedGameType === $id) ? ' selected' : '';
        echo '<option value="', htmlspecialchars($id), '"', $selected, '>', $icon, ' ', htmlspecialchars($name), '</option>';
    }
    
    echo '
                </select>
            </form>
        </div>';
    
    // Show detail view if game type selected, otherwise show grid
    if (!empty($selectedGameType) && !empty($gameTypeData)) {
        $gtName = $gameTypeData['name'] ?? strtoupper($selectedGameType);
        $gtIcon = $gameTypeData['icon'] ?? '🎮';
        $gtDesc = $gameTypeData['description'] ?? '';
        
        echo '
        <div class="cat_bar">
            <h3 class="catbg">', $gtIcon, ' ', htmlspecialchars($gtName), ' - Detailed Statistics</h3>
        </div>
        
        <div class="mohaa-gametype-detail-stats">
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">⚔️</div>
                <div class="stat-value">', number_format((int)($gameTypeData['total_matches'] ?? 0)), '</div>
                <div class="stat-label">Matches Played</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">💀</div>
                <div class="stat-value">', number_format((int)($gameTypeData['total_kills'] ?? 0)), '</div>
                <div class="stat-label">Total Kills</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-value">', number_format((int)($gameTypeData['map_count'] ?? 0)), '</div>
                <div class="stat-label">Maps</div>
            </div>
            <div class="mohaa-gametypes-stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value">', number_format((int)($gameTypeData['unique_players'] ?? 0)), '</div>
                <div class="stat-label">Unique Players</div>
            </div>
        </div>
        
        <div class="mohaa-gametype-detail" style="margin-top: 20px;">
            <div class="mohaa-gametypes-chart-card">
                <h4>🗺️ Maps in ', htmlspecialchars($gtName), '</h4>
                <div class="mohaa-gametype-maps-list">
                    <table class="table_grid" style="width: 100%;">
                        <thead>
                            <tr class="title_bar">
                                <th>Map</th>
                                <th>Matches</th>
                                <th>Kills</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        $maps = $gameTypeData['maps'] ?? [];
        if (!empty($maps)) {
            foreach ($maps as $map) {
                echo '
                            <tr class="windowbg">
                                <td>
                                    <a href="', $scripturl, '?action=mohaastats;sa=maps;map=', urlencode($map['name'] ?? ''), '">
                                        ', htmlspecialchars($map['display_name'] ?? $map['name'] ?? 'Unknown'), '
                                    </a>
                                </td>
                                <td>', number_format((int)($map['matches'] ?? 0)), '</td>
                                <td>', number_format((int)($map['kills'] ?? 0)), '</td>
                            </tr>';
            }
        } else {
            echo '<tr class="windowbg"><td colspan="3" style="text-align: center; color: #888;">No maps data</td></tr>';
        }
        
        echo '
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mohaa-gametypes-chart-card">
                <h4>🏆 Top Players in ', htmlspecialchars($gtName), '</h4>
                <table class="table_grid" style="width: 100%;">
                    <thead>
                        <tr class="title_bar">
                            <th>#</th>
                            <th>Player</th>
                            <th>Kills</th>
                            <th>Deaths</th>
                            <th>K/D</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (!empty($leaderboard)) {
            foreach (array_slice($leaderboard, 0, 15) as $rank => $player) {
                $kills = (int)($player['kills'] ?? 0);
                $deaths = (int)($player['deaths'] ?? 0);
                $kd = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
                $kdClass = $kd >= 2 ? 'color: #4caf50;' : ($kd >= 1 ? 'color: #ffc107;' : 'color: #f44336;');
                
                echo '
                        <tr class="windowbg">
                            <td><strong>', $rank + 1, '</strong></td>
                            <td>
                                <a href="', $scripturl, '?action=mohaastats;sa=player;id=', urlencode($player['id'] ?? ''), '">
                                    ', htmlspecialchars($player['name'] ?? 'Unknown'), '
                                </a>
                            </td>
                            <td>', number_format($kills), '</td>
                            <td>', number_format($deaths), '</td>
                            <td style="', $kdClass, ' font-weight: bold;">', $kd, '</td>
                        </tr>';
            }
        } else {
            echo '<tr class="windowbg"><td colspan="5" style="text-align: center; color: #888;">No player data</td></tr>';
        }
        
        echo '
                    </tbody>
                </table>
            </div>
        </div>';
    } else {
        // Show all game types grid
        echo '
        <div class="cat_bar" style="margin-top: 10px;">
            <h3 class="catbg">🎮 All Game Types</h3>
        </div>
        
        <div class="mohaa-gametypes-grid">';
        
        foreach ($gameTypes as $gt) {
            $id = $gt['id'] ?? '';
            $name = $gt['name'] ?? strtoupper($id);
            $icon = $gt['icon'] ?? '🎮';
            $desc = $gt['description'] ?? '';
            $matches = (int)($gt['total_matches'] ?? 0);
            $kills = (int)($gt['total_kills'] ?? 0);
            $mapCount = (int)($gt['map_count'] ?? 0);
            
            echo '
            <a href="', $scripturl, '?action=mohaastats;sa=gametypes;gametype=', urlencode($id), '" class="mohaa-gametype-card">
                <div class="mohaa-gametype-card-header">
                    <div class="icon">', $icon, '</div>
                    <div class="name">', htmlspecialchars($name), '</div>
                    <div class="description">', htmlspecialchars($desc), '</div>
                </div>
                <div class="mohaa-gametype-card-stats">
                    <div class="stat">
                        <div class="stat-value">', number_format($matches), '</div>
                        <div class="stat-label">Matches</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">', number_format($kills), '</div>
                        <div class="stat-label">Kills</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">', number_format($mapCount), '</div>
                        <div class="stat-label">Maps</div>
                    </div>
                </div>
            </a>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
    
    // ApexCharts JavaScript
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var gameTypes = ', json_encode(array_map(function($gt) {
            return [
                'name' => $gt['name'] ?? strtoupper($gt['id'] ?? ''),
                'matches' => (int)($gt['total_matches'] ?? 0),
                'kills' => (int)($gt['total_kills'] ?? 0),
            ];
        }, $gameTypes)), ';
        
        // Popularity bar chart
        if (document.getElementById("gametypePopularityChart") && gameTypes.length > 0) {
            new ApexCharts(document.getElementById("gametypePopularityChart"), {
                series: [{
                    name: "Matches",
                    data: gameTypes.map(function(g) { return g.matches; })
                }],
                chart: { type: "bar", height: 300, background: "transparent", toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                dataLabels: { enabled: true },
                xaxis: { categories: gameTypes.map(function(g) { return g.name; }) },
                colors: ["#4fc3f7"],
                theme: { mode: "dark" }
            }).render();
        }
        
        // Kills donut chart
        if (document.getElementById("gametypeKillsChart") && gameTypes.length > 0) {
            new ApexCharts(document.getElementById("gametypeKillsChart"), {
                series: gameTypes.map(function(g) { return g.kills; }),
                chart: { type: "donut", height: 300, background: "transparent" },
                labels: gameTypes.map(function(g) { return g.name; }),
                colors: ["#e91e63", "#9c27b0", "#673ab7", "#3f51b5", "#2196f3", "#00bcd4", "#009688"],
                legend: { position: "right", labels: { colors: "#fff" } },
                theme: { mode: "dark" }
            }).render();
        }
    });
    </script>';
}
