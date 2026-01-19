<?php
/**
 * MOHAA Enhanced Stats Dashboard - War Room
 * Hybrid Design: Modern Grid Layout + SMF Integration
 *
 * @package MohaaPlayers
 * @version 2.3.0
 */

/**
 * Enhanced Player Dashboard - The War Room
 */
function template_mohaa_war_room()
{
    global $context, $txt, $scripturl, $user_info;

    $data = $context['mohaa_dashboard'];
    $player = $data['player_stats'] ?? [];
    $member = $data['member'] ?? [];

    // Inject Modern CSS for Dashboard
    echo '
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --mohaa-accent: #4a6b8a; /* Soft blue-grey */
            --mohaa-success: #4caf50;
            --mohaa-warning: #ff9800;
            --mohaa-danger: #f44336;
            --mohaa-card-bg: rgba(255,255,255,0.05); /* Slight tint for cards */
        }
        
        .mohaa-dashboard-container {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin-bottom: 20px;
        }

        /* Stats Grid System */
        .mohaa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Dashboard Card Style */
        .stat-card {
            background: var(--mohaa-card-bg); /* Fallback or override if windowbg is not enough */
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--mohaa-accent);
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: inherit; /* Inherit from theme */
            opacity: 0.9;
        }
        
        /* Header Profile */
        .profile-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(0,0,0,0.1), rgba(0,0,0,0));
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .rank-icon { font-size: 3.5em; line-height: 1; }
        
        .profile-info h1 { margin: 0; font-size: 2em; line-height: 1.2; }
        .profile-meta { opacity: 0.7; font-size: 0.9em; }
        .tag-badge { 
            background: var(--mohaa-accent); 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-weight: bold;
        }
        
        .header-stats {
            display: flex;
            gap: 15px;
            margin-left: auto;
        }
        
        .mini-stat {
            text-align: center;
            padding: 10px 15px;
            border-radius: 6px;
            background: rgba(0,0,0,0.2); /* Darker for contrast */
            min-width: 80px;
        }
        
        .mini-stat .value { display: block; font-size: 1.4em; font-weight: bold; }
        .mini-stat .label { font-size: 0.7em; text-transform: uppercase; opacity: 0.8; }
        
        /* Tabs */
        .mohaa-tabs {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid var(--mohaa-accent);
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .mohaa-tab {
            padding: 12px 20px;
            background: rgba(0,0,0,0.1);
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .mohaa-tab:hover { background: rgba(0,0,0,0.2); text-decoration: none; }
        .mohaa-tab.active {
            background: var(--mohaa-accent);
            color: #fff;
        }

        /* Component Specifics */
        .gauge-svg { width: 100%; height: auto; max-height: 150px; }
        
        .weapon-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .weapon-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .map-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .streak-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .streak-item { text-align: center; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 6px; }
        
        /* Clean Tables */
        .clean-table { width: 100%; border-collapse: collapse; }
        .clean-table th { text-align: left; padding: 12px; border-bottom: 2px solid rgba(0,0,0,0.1); opacity: 0.7; }
        .clean-table td { padding: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .clean-table tr:hover td { background: rgba(0,0,0,0.02); }
    </style>
    
    <div class="mohaa-dashboard-container">
        <!-- Header -->
        <div class="windowbg profile-header">
            <div class="rank-icon">
                ', template_war_room_rank_icon($player['kills'] ?? 0), '
            </div>
            <div class="profile-info">
                <h1>', htmlspecialchars($member['real_name'] ?? $member['member_name'] ?? 'Soldier'), '</h1>
                <div class="profile-meta">
                    <span class="tag-badge">', htmlspecialchars($player['clan_tag'] ?? 'N/A'), '</span>
                    <span>ELO: <strong>', number_format($player['elo'] ?? 1000), '</strong></span>
                </div>
            </div>
            
            <div class="header-stats">
                <div class="mini-stat">
                    <span class="value">', number_format($player['kills'] ?? 0), '</span>
                    <span class="label">Kills</span>
                </div>
                <div class="mini-stat">
                    <span class="value">', number_format($player['deaths'] ?? 0), '</span>
                    <span class="label">Deaths</span>
                </div>
                <div class="mini-stat">
                    <span class="value" style="color: ', (($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1) >= 1 ? 'var(--mohaa-success)' : 'var(--mohaa-danger)'), '">
                        ', number_format(($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1), 2), '
                    </span>
                    <span class="label">K/D</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mohaa-tabs">
            <a onclick="showTab(\'combat\')" class="mohaa-tab active">⚔️ Combat</a>
            <a onclick="showTab(\'weapons\')" class="mohaa-tab">🔫 Armoury</a>
            <a onclick="showTab(\'movement\')" class="mohaa-tab">🏃 Movement</a>
            <a onclick="showTab(\'gameflow\')" class="mohaa-tab">🎮 Game</a>
            <a onclick="showTab(\'tactical\')" class="mohaa-tab">🎯 Tactical</a>
            <a onclick="showTab(\'maps\')" class="mohaa-tab">🗺️ Maps</a>
            <a onclick="showTab(\'matches\')" class="mohaa-tab">📊 Matches</a>
            <a onclick="showTab(\'achievements\')" class="mohaa-tab">🏆 Medals</a>
        </div>
        
        <!-- ======================= COMBAT TAB ======================= -->
        <div id="tab-combat" class="tab-content" style="display: block;">
            <div class="mohaa-grid">
                <!-- Performance Trend (Wide) -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>Performance Trend (Last 20 Matches)</h3>
                    <div id="chart-performance" style="min-height: 250px;"></div>
                </div>

                <!-- K/D Gauge -->
                <div class="windowbg stat-card">
                    <h3>Performance Gauge</h3>
                    <div style="text-align: center; flex: 1; display: flex; align-items: center; justify-content: center;">
                        ', template_war_room_kdr_gauge_content($player), '
                    </div>
                </div>
                
                <!-- Hit Silhouette -->
                <div class="windowbg stat-card">
                    <h3>Hit Distribution</h3>
                    ', template_war_room_silhouette_content($player), '
                </div>
                
                <!-- Kill Streaks -->
                <div class="windowbg stat-card">
                    <h3>Kill Streaks</h3>
                    ', template_war_room_streaks_content($player), '
                </div>
                
                <!-- Accuracy -->
                <div class="windowbg stat-card">
                    <h3>Accuracy</h3>
                    ', template_war_room_accuracy_content($player, $data), '
                </div>
                
                <!-- Special Stats -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>Special Achievements</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        ', template_war_room_special_stats_content($player), '
                    </div>
                </div>
                
                <!-- Skill Spider (Radar Chart) -->
                <div class="windowbg stat-card">
                    <h3>🕷️ Skill Profile</h3>
                    <div id="chart-skill-spider" style="min-height: 280px;"></div>
                </div>
                
                <!-- Recent Achievements (Horizontal Scroll) -->
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🏆 Recent Achievements</h3>
                    <div class="achievements-scroll" style="display: flex; gap: 15px; overflow-x: auto; padding: 10px 0;">
                        ';
                        
                $achievements = $player['achievements'] ?? [];
                if (empty($achievements)) {
                    echo '<div style="opacity: 0.6; padding: 20px; text-align: center; width: 100%;">No achievements yet. Keep playing!</div>';
                } else {
                    foreach (array_slice($achievements, 0, 10) as $ach) {
                        $tierIcon = match($ach['tier'] ?? 1) {
                            1 => '🟫', 2 => '⬜', 3 => '🟨', 4 => '💎', 5 => '💠',
                            default => '🏅'
                        };
                        echo '
                        <div style="min-width: 120px; text-align: center; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px; flex-shrink: 0;">
                            <div style="font-size: 2em;">', $tierIcon, '</div>
                            <div style="font-weight: bold; margin-top: 5px; font-size: 0.9em;">', htmlspecialchars($ach['name'] ?? 'Unknown'), '</div>
                            <div style="font-size: 0.7em; opacity: 0.7;">', htmlspecialchars($ach['description'] ?? ''), '</div>
                        </div>';
                    }
                }
                echo '
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <a href="', $scripturl, '?action=medals" style="font-size: 0.9em;">View All Medals →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================= WEAPONS TAB ======================= -->
        <div id="tab-weapons" class="tab-content" style="display: none;">
            <div class="windowbg stat-card" style="margin-bottom: 20px;">
                <h3>Weapon Usage Distribution</h3>
                <div id="chart-weapons" style="min-height: 300px;"></div>
            </div>
            <div class="windowbg stat-card">
                <h3>Weapon Mastery</h3>
                ', template_war_room_weapons_content($player['weapons'] ?? []), '
            </div>
        </div>
        
        <!-- ======================= MOVEMENT TAB ======================= -->
        <div id="tab-movement" class="tab-content" style="display: none;">
            <div class="mohaa-grid">
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🏃 Distance Stats</h3>
                    ', template_war_room_distance_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🧍 Stance Analysis</h3>
                    ', template_war_room_stance_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🦘 Jump Stats</h3>
                    ', template_war_room_jumps_content($player), '
                </div>
            </div>
        </div>
        
        <!-- ======================= GAME FLOW TAB ======================= -->
        <div id="tab-gameflow" class="tab-content" style="display: none;">
            <div class="mohaa-grid">
                <div class="windowbg stat-card">
                    <h3>🏆 Win/Loss Record</h3>
                    ', template_war_room_winloss_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>🔄 Rounds & Games</h3>
                    ', template_war_room_rounds_content($player), '
                </div>
                <div class="windowbg stat-card" style="grid-column: 1 / -1;">
                    <h3>🎯 Objectives</h3>
                    ', template_war_room_objectives_content($player), '
                </div>
            </div>
        </div>
        
        <!-- ======================= TACTICAL TAB ======================= -->
        <div id="tab-tactical" class="tab-content" style="display: none;">
            <div class="mohaa-grid">
                <div class="windowbg stat-card">
                    <h3>Movement Profile</h3>
                    ', template_war_room_movement_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>Stance Analysis</h3>
                    ', template_war_room_stance_content($player), '
                </div>
                <div class="windowbg stat-card">
                    <h3>Rivals</h3>
                    ', template_war_room_rivals_content($player), '
                </div>
                
                 <!-- Playstyle Widget -->
                <div class="windowbg stat-card">
                    <h3>Playstyle Analysis</h3>
                    ', template_war_room_playstyle_content($player['playstyle'] ?? []), '
                </div>
            </div>
        </div>
        
        <!-- ======================= MAPS TAB ======================= -->
        <div id="tab-maps" class="tab-content" style="display: none;">
            <div class="windowbg stat-card">
                <h3>Map Performance</h3>
                ', template_war_room_maps_content($player['maps'] ?? [], $player), '
            </div>
        </div>
        
         <!-- ======================= ACHIEVEMENTS TAB ======================= -->
        <div id="tab-achievements" class="tab-content" style="display: none;">
             <div class="windowbg stat-card">
                <h3>Unlocked Achievements</h3>
                ', template_war_room_achievements_content($data['mohaa_my']['achievements'] ?? []), '
            </div>
        </div>
        
         <!-- ======================= MATCHES TAB ======================= -->
        <div id="tab-matches" class="tab-content" style="display: none;">
             <div class="windowbg stat-card">
                <h3>Recent Match History</h3>
                <div style="overflow-x: auto;">
                    ', template_war_room_matches_content($player['recent_matches'] ?? []), '
                </div>
            </div>
        </div>

    </div>

    <!-- Pass Data to JS -->
    <script>
        window.mohaaData = ' . json_encode($context['mohaa_dashboard']) . ';
        
        document.addEventListener("DOMContentLoaded", function() {
            initWarRoomCharts();
        });
        
        function initWarRoomCharts() {
            const data = window.mohaaData;
            const player = data.player_stats || {};
            const perf = player.performance || []; // Expecting array of {kd: float, played_at: timestamp}
            
            // 1. Performance Trend (Area Chart)
            const perfCtx = document.querySelector("#chart-performance");
            if (perfCtx && perf.length > 0) {
                const options = {
                    series: [{
                        name: "K/D Ratio",
                        data: perf.map(m => parseFloat(m.kd).toFixed(2))
                    }],
                    chart: {
                        type: "area",
                        height: 250,
                        toolbar: { show: false },
                        background: "transparent",
                        animations: { enabled: true, easing: "easeinout", speed: 800 }
                    },
                    colors: ["#4a6b8a"],
                    fill: {
                        type: "gradient",
                        gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100] }
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: "smooth", width: 2 },
                    xaxis: {
                        categories: perf.map(m => new Date(m.played_at * 1000).toLocaleDateString()),
                        labels: { style: { colors: "#888", fontSize: "10px" } }
                    },
                    yaxis: {
                        labels: { style: { colors: "#888" } },
                        title: { text: "K/D Ratio", style: { color: "#888" } }
                    },
                    theme: { mode: "dark" },
                    grid: { borderColor: "#444", strokeDashArray: 4 },
                    tooltip: { theme: "dark" }
                };
                new ApexCharts(perfCtx, options).render();
            } else if (perfCtx) {
                perfCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 80px; opacity: 0.6;\'>Play more matches to see your trend!</p>";
            }
            
            // 2. Weapon Distribution (Donut)
            const weapCtx = document.querySelector("#chart-weapons");
            const weapons = player.weapons || {}; // Object: name -> stats
            // Convert object to array for sorting
            const weaponArr = Array.isArray(weapons) ? weapons : Object.entries(weapons)
                .map(([k, v]) => ({name: k, kills: v.kills}))
                .filter(w => w.kills > 0)
                .sort((a, b) => b.kills - a.kills)
                .slice(0, 8); // Top 8
                
            if (weapCtx && weaponArr.length > 0) {
                const options = {
                    series: weaponArr.map(w => parseInt(w.kills)),
                    labels: weaponArr.map(w => w.name),
                    chart: { type: "donut", height: 300, background: "transparent" },
                    plotOptions: { 
                        pie: { 
                            donut: { 
                                size: "70%",
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: "Total Kills",
                                        color: "#fff",
                                        formatter: function (w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            } 
                        } 
                    },
                    stroke: { show: false },
                    theme: { mode: "dark", palette: "palette2" }, // Using palette2 for variety
                    legend: { position: "bottom", labels: { colors: "#fff" } },
                    dataLabels: { enabled: false }
                };
                new ApexCharts(weapCtx, options).render();
            } else if (weapCtx) {
                 weapCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 100px; opacity: 0.6;\'>No weapon data recorded yet.</p>";
            }
            
            // 3. Map Analysis (Radar)
            const mapCtx = document.querySelector("#chart-maps");
            const maps = player.maps || {};
            // Filter maps with data
            const mapArr = Array.isArray(maps) ? maps : Object.entries(maps)
                .map(([name, stats]) => ({
                    name: name.split("/").pop().replace(/^obj_|dm_/, ""), 
                    winRate: ((stats.wins || 0) / Math.max(1, (stats.matches || stats.kills/10))) * 100, // Approx matches if missing
                    kills: stats.kills || 0
                }))
                .filter(m => m.kills > 5) // Lower threshold
                .sort((a, b) => b.kills - a.kills)
                .slice(0, 6);
                
            if (mapCtx && mapArr.length > 0) {
                const options = {
                    series: [{
                        name: "Win Rate %",
                        data: mapArr.map(m => Math.min(100, parseFloat(m.winRate).toFixed(1)))
                    }],
                    chart: { type: "radar", height: 350, background: "transparent", toolbar: { show: false } },
                    xaxis: { 
                        categories: mapArr.map(m => m.name), 
                        labels: { 
                            style: { 
                                colors: ["#fff", "#fff", "#fff", "#fff", "#fff", "#fff"],
                                fontSize: "11px"
                            } 
                        } 
                    },
                    stroke: { width: 2, colors: ["#4caf50"] },
                    fill: { opacity: 0.2, colors: ["#4caf50"] },
                    markers: { size: 4, colors: ["#fff"], strokeColors: "#4caf50", strokeWidth: 2 },
                    theme: { mode: "dark" },
                    yaxis: { max: 100, tickAmount: 4, labels: { style: { colors: "#888" } } },
                    tooltip: { theme: "dark" }
                };
                new ApexCharts(mapCtx, options).render();
            } else if (mapCtx) {
                 mapCtx.innerHTML = "<p class=\'centertext\' style=\'padding-top: 100px; opacity: 0.6;\'>Not enough map data yet.</p>";
            }
            
            // 4. Skill Spider (Radar) - NEW
            const spiderCtx = document.querySelector("#chart-skill-spider");
            if (spiderCtx) {
                // Calculate skill values from player stats (0-100 scale)
                const accuracy = Math.min(100, (player.accuracy || 0) * 2.5); // 40% acc = 100 score
                const aggression = Math.min(100, ((player.kills || 0) / Math.max(1, player.playtime_hours || 1)) * 10); // Kills per hour
                const survival = Math.min(100, (player.kd_ratio || 1) * 30); // 3.0 KD = 90
                const movement = Math.min(100, ((player.distance_km || 0) / Math.max(1, player.playtime_hours || 1)) * 5); // KM per hour
                const clutch = Math.min(100, ((player.clutch_wins || 0) / Math.max(1, (player.clutch_total || 1))) * 100); // Clutch win %
                
                const options = {
                    series: [{
                        name: "You",
                        data: [accuracy, aggression, survival, movement, clutch]
                    }],
                    chart: { 
                        type: "radar", 
                        height: 280, 
                        background: "transparent", 
                        toolbar: { show: false }
                    },
                    xaxis: { 
                        categories: ["Accuracy", "Aggression", "Survival", "Movement", "Clutch"],
                        labels: { 
                            style: { 
                                colors: ["#4caf50", "#ff9800", "#2196f3", "#9c27b0", "#f44336"],
                                fontSize: "12px",
                                fontWeight: "bold"
                            } 
                        }
                    },
                    stroke: { width: 2, colors: ["#4a6b8a"] },
                    fill: { opacity: 0.3, colors: ["#4a6b8a"] },
                    markers: { size: 4, colors: ["#fff"], strokeColors: "#4a6b8a", strokeWidth: 2 },
                    theme: { mode: "dark" },
                    yaxis: { max: 100, tickAmount: 4, labels: { style: { colors: "#888" } } },
                    tooltip: { theme: "dark", y: { formatter: (val) => Math.round(val) + "%" } }
                };
                new ApexCharts(spiderCtx, options).render();
            }
        }
        
        function showTab(tabName) {
            // Hide all tabs
            var content = document.getElementsByClassName("tab-content");
            for (var i = 0; i < content.length; i++) {
                content[i].style.display = "none";
            }
            // Show selected
            document.getElementById("tab-" + tabName).style.display = "block";
            
            // Update buttons
            var buttons = document.getElementsByClassName("mohaa-tab");
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove("active");
                if (buttons[i].getAttribute("onclick").includes(tabName)) {
                    buttons[i].classList.add("active");
                }
            }
        }
    </script>
    ';
}

// =========================================================================
// HELPER FUNCTIONS (Pure Content Generation, Minimal Styling)
// =========================================================================

function template_war_room_kdr_gauge_content($player) {
    $kdr = ($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1);
    $percent = min(100, ($kdr / 5) * 100);
    $offset = 251.2 - (251.2 * $percent / 100);
    
    return '
    <div style="position: relative; width: 220px;">
        <svg viewBox="0 0 200 120" class="gauge-svg">
            <path d="M20,100 A80,80 0 0,1 180,100" fill="none" class="gauge-bg" stroke="rgba(128,128,128,0.2)" stroke-width="15" stroke-linecap="round"/>
            <path d="M20,100 A80,80 0 0,1 180,100" fill="none" stroke="var(--mohaa-accent)" stroke-width="15" stroke-linecap="round" stroke-dasharray="251.2" stroke-dashoffset="'.$offset.'"/>
            <text x="100" y="85" font-size="2.2em" font-weight="bold" text-anchor="middle" fill="currentColor">'.number_format($kdr, 2).'</text>
            <text x="100" y="105" font-size="0.8em" text-anchor="middle" opacity="0.6" fill="currentColor">Ratio</text>
        </svg>
    </div>';
}

function template_war_room_silhouette_content($player) {
    $kills = max(1, $player['kills'] ?? 1);
    
    // Calculation functions
    $calcPct = function($val, $total) {
        return $total > 0 ? round(($val / $total) * 100, 1) : 0;
    };
    
    // Outgoing (Hits Dealt)
    $head = $player['headshots'] ?? 0;
    $torso = $player['torso_kills'] ?? ($kills * 0.4); 
    // Limbs is remainder
    $limbs = max(0, $kills - $head - $torso);
    
    $outHeadPct = $calcPct($head, $kills);
    $outTorsoPct = $calcPct($torso, $kills);
    $outLimbPct = $calcPct($limbs, $kills);

    // Incoming (Hits Taken) - extracting from 'deaths' or using placeholders if specific hitloc data missing
    $deaths = max(1, $player['deaths'] ?? 1);
    // Note: API might not provide 'headshots_received' yet, simulating or checking standard fields
    // If fields exist use them, else estimate or show 0
    $inHead = $player['headshots_received'] ?? 0; // Hypothetical field
    $inTorso = $player['torso_deaths'] ?? 0;
    $inLimbs = $player['limb_deaths'] ?? 0;
    
    // If no specific death data, calculate 'unknown' remainder
    $knownDeaths = $inHead + $inTorso + $inLimbs;
    if ($knownDeaths == 0) {
        // Fallback/Placeholder if data missing: Estimate standard distribution or show empty
        // Showing empty/grey for accuracy if data isn't tracked yet
        $inHeadPct = 0; $inTorsoPct = 0; $inLimbPct = 0;
    } else {
        $inHeadPct = $calcPct($inHead, $deaths);
        $inTorsoPct = $calcPct($inTorso, $deaths);
        $inLimbPct = $calcPct($inLimbs, $deaths);
    }
    
    $renderMan = function($title, $hPct, $tPct, $lPct) {
        // Opacity based on percentage (min 0.2 for visibility)
        $hOp = max(0.1, min(1, $hPct/100 * 2)); // Amplify for visibility
        $tOp = max(0.1, min(1, $tPct/100 * 1.5));
        $lOp = max(0.1, min(1, $lPct/100 * 1.5));
        
        return '
        <div style="text-align: center; flex: 1;">
            <h4 style="margin: 0 0 10px 0; font-size: 0.9em; text-transform: uppercase; color: var(--mohaa-accent);">'.$title.'</h4>
            <div style="display: flex; gap: 15px; justify-content: center; align-items: center;">
                
                <!-- Improved Silhouette SVG -->
                <svg viewBox="0 0 140 220" style="width: 100px; height: 160px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
                    <!-- Head -->
                    <circle cx="70" cy="25" r="18" fill="#f44336" fill-opacity="'.$hOp.'" />
                    
                    <!-- Torso -->
                    <rect x="50" y="48" width="40" height="70" rx="4" fill="#ff9800" fill-opacity="'.$tOp.'" />
                    
                    <!-- Arms -->
                    <rect x="25" y="50" width="18" height="60" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    <rect x="97" y="50" width="18" height="60" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    
                    <!-- Legs -->
                    <rect x="50" y="122" width="18" height="80" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                    <rect x="72" y="122" width="18" height="80" rx="4" fill="#2196f3" fill-opacity="'.$lOp.'" />
                </svg>
                
                <div style="text-align: left; font-size: 0.85em; width: 80px;">
                    <div style="margin-bottom: 5px;"><strong style="color: #f44336;">Head</strong><br>'.$hPct.'%</div>
                    <div style="margin-bottom: 5px;"><strong style="color: #ff9800;">Torso</strong><br>'.$tPct.'%</div>
                    <div><strong style="color: #2196f3;">Limbs</strong><br>'.$lPct.'%</div>
                </div>
            </div>
        </div>';
    };

    return '
    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px;">' . 
        $renderMan('HITS DEALT', $outHeadPct, $outTorsoPct, $outLimbPct) . 
        '<div style="width: 1px; background: rgba(0,0,0,0.1);"></div>' .
        $renderMan('HITS TAKEN', $inHeadPct, $inTorsoPct, $inLimbPct) . 
    '</div>';
}

function template_war_room_streaks_content($player) {
    return '
    <div class="streak-grid">
        <div class="streak-item">
            <div style="font-size: 1.8em; font-weight: bold; color: var(--mohaa-warning);">'.($player['best_killstreak'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Best Streak</div>
        </div>
        <div class="streak-item">
            <div style="font-size: 1.8em; font-weight: bold;">'.($player['streaks_5'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Rampages (5+)</div>
        </div>
        <div class="streak-item" style="grid-column: span 2;">
            <div style="font-size: 1.2em; font-weight: bold;">'.($player['streaks_10'] ?? 0).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Dominations (10+)</div>
        </div>
    </div>';
}

function template_war_room_accuracy_content($player, $data) {
    $accuracy = $player['accuracy'] ?? 0;
    $serverAvg = $data['server_avg_accuracy'] ?? 25;
    
    return '
    <div style="text-align: center; padding: 15px;">
        <div style="font-size: 2.5em; font-weight: bold; color: var(--mohaa-success);">'.number_format($accuracy, 1).'%</div>
        <div style="font-size: 0.9em; opacity: 0.7; margin-bottom: 15px;">Target Hit Rate</div>
        
        <div style="background: rgba(0,0,0,0.1); border-radius: 4px; padding: 10px; font-size: 0.9em;">
            Server Avg: <strong>'.number_format($serverAvg, 1).'%</strong>
        </div>
    </div>';
}

function template_war_room_special_stats_content($player) {
    $specials = [
        '🥜 Nutshots' => $player['nutshots'] ?? 0,
        '🗡️ Backstabs' => $player['backstabs'] ?? 0,
        '💥 Wallbangs' => $player['wallbangs'] ?? 0,
        '🩸 First Blood' => $player['first_bloods'] ?? 0,
         '☠️ Multi-Kills' => ($player['multi_5plus'] ?? 0),
         '🔥 Clutches' => ($player['clutches'] ?? 0),
    ];
    
    $html = '';
    foreach ($specials as $label => $val) {
        $html .= '
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 6px;">
            <span>'.$label.'</span>
            <strong style="font-size: 1.2em;">'.number_format($val).'</strong>
        </div>';
    }
    return $html;
}

function template_war_room_weapons_content($weapons) {
    if (empty($weapons)) return '<p style="padding: 20px; text-align: center; opacity: 0.6;">No weapon data recorded yet.</p>';
    
    $html = '<div class="weapon-list-grid">';
    foreach ($weapons as $name => $stats) {
        $icon = template_war_room_weapon_icon($name);
        $kills = $stats['kills'] ?? 0;
        $acc = $stats['accuracy'] ?? 0;
        
        // Simple mastery progress
        $progress = min(100, ($kills / 1000) * 100);
        
        $html .= '
        <div class="weapon-card">
            <div style="font-size: 2em;">'.$icon.'</div>
            <div style="flex: 1;">
                <div style="font-weight: bold; font-size: 1.1em;">'.htmlspecialchars($name).'</div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85em; opacity: 0.8; margin: 5px 0;">
                    <span>'.$kills.' Kills</span>
                    <span>'.number_format($acc,1).'% Acc</span>
                </div>
                <div style="height: 4px; background: rgba(0,0,0,0.1); border-radius: 2px;">
                    <div style="width: '.$progress.'%; height: 100%; background: var(--mohaa-accent); border-radius: 2px;"></div>
                </div>
            </div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function template_war_room_movement_content($player) {
    return '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: center;">
        <div class="streak-item">
            <div style="font-size: 2em; margin-bottom: 5px;">🏃</div>
            <strong>'.number_format(($player['distance_traveled'] ?? 0) / 1000, 1).' km</strong>
            <div style="font-size: 0.8em; opacity: 0.7;">Traveled</div>
        </div>
        <div class="streak-item">
            <div style="font-size: 2em; margin-bottom: 5px;">🐇</div>
            <strong>'.number_format($player['jumps'] ?? 0).'</strong>
            <div style="font-size: 0.8em; opacity: 0.7;">Jumps</div>
        </div>
    </div>';
}

function template_war_room_stance_content($player) {
    return '
    <div style="padding: 10px;">
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Standing</span>
                <strong>'.($player['standing_kills_pct'] ?? 0).'%</strong>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.($player['standing_kills_pct'] ?? 0).'%; height: 100%; background: #2196f3; border-radius: 4px;"></div>
            </div>
        </div>
         <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Crouching</span>
                <strong>'.($player['crouching_kills_pct'] ?? 0).'%</strong>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.($player['crouching_kills_pct'] ?? 0).'%; height: 100%; background: #4caf50; border-radius: 4px;"></div>
            </div>
        </div>
         <div>
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>Prone</span>
                <strong>'.($player['prone_kills_pct'] ?? 0).'%</strong>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.($player['prone_kills_pct'] ?? 0).'%; height: 100%; background: #ff9800; border-radius: 4px;"></div>
            </div>
        </div>
    </div>';
}

function template_war_room_rivals_content($player) {
    return '
    <div style="display: grid; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border-left: 3px solid #f44336; background: rgba(244, 67, 54, 0.05);">
            <div style="font-size: 1.5em;">😡</div>
            <div>
                <div style="font-size: 0.8em; color: #f44336; font-weight: bold;">NEMESIS</div>
                <div style="font-weight: bold;">'.htmlspecialchars($player['nemesis_name'] ?? 'None').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Result: '.($player['nemesis_kills'] ?? 0).' deaths</div>
            </div>
        </div>
         <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border-left: 3px solid #4caf50; background: rgba(76, 175, 80, 0.05);">
             <div style="font-size: 1.5em;">😈</div>
            <div>
                <div style="font-size: 0.8em; color: #4caf50; font-weight: bold;">VICTIM</div>
                <div style="font-weight: bold;">'.htmlspecialchars($player['victim_name'] ?? 'None').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">Result: '.($player['victim_kills'] ?? 0).' kills</div>
            </div>
        </div>
    </div>';
}

function template_war_room_maps_content($maps, $player) {
    if (empty($maps)) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">No map data.</p>';
    
    $html = '<div class="map-card-grid">';
    foreach ($maps as $name => $stats) {
        $isBest = $name === ($player['best_map'] ?? '');
        $style = $isBest ? 'border-color: var(--mohaa-success); background: rgba(76, 175, 80, 0.1);' : '';
        
        $html .= '
        <div class="stat-card" style="padding: 15px; text-align: center; '.$style.'">
            <div style="font-weight: bold; margin-bottom: 5px;">'.htmlspecialchars($name).'</div>
            '.($isBest ? '<div style="font-size: 0.7em; color: var(--mohaa-success); font-weight: bold; margin-bottom: 5px;">BEST MAP</div>' : '').'
            <div style="font-size: 0.9em;">
                <div>Kills: <strong>'.($stats['kills'] ?? 0).'</strong></div>
                <div>Wins: '.($stats['wins'] ?? 0).'</div>
            </div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function template_war_room_matches_content($matches) {
    if (empty($matches)) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">No matches played recently.</p>';
    
    $html = '<table class="clean-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Map</th>
                        <th>Result</th>
                        <th>Kills</th>
                        <th>Deaths</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>';
                
    foreach ($matches as $m) {
        $res = $m['result'] ?? 'draw';
        $resClass = $res === 'win' ? 'color: var(--mohaa-success);' : ($res === 'loss' ? 'color: var(--mohaa-danger);' : '');
        
        $html .= '
        <tr>
            <td>'.timeformat($m['date'] ?? time(), '%b %d').'</td>
            <td>'.htmlspecialchars($m['map'] ?? 'Unknown').'</td>
            <td style="font-weight: bold; '.$resClass.'">'.strtoupper($res).'</td>
            <td>'.($m['kills'] ?? 0).'</td>
            <td>'.($m['deaths'] ?? 0).'</td>
            <td>'.($m['score'] ?? 0).'</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}



function template_war_room_achievements_content($achievements) {
    // API returns {unlocked: [], progress: []}
    $unlocked = $achievements['unlocked'] ?? [];
    
    if (empty($unlocked)) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">No achievements unlocked yet.</p>';
    
    $html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">';
    
    foreach ($unlocked as $ach) {
        // Icon mapping or default
        $icon = "🎖️"; // Default
        
        $html .= '
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="font-size: 2em;">'.$icon.'</div>
            <div>
                <div style="font-weight: bold; font-size: 0.9em;">'.htmlspecialchars($ach['name'] ?? 'Achievement').'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">'.timeformat($ach['unlocked_date'] ?? time(), '%b %d, %Y').'</div>
            </div>
        </div>';
    }
    
    $html .= '</div>
    <div style="margin-top: 20px; text-align: center;">
        <a href="' . $GLOBALS['scripturl'] . '?action=mohaaachievements" class="button">View Full Medal Case</a>
    </div>';
    return $html;
}

function template_war_room_playstyle_content($playstyle) {
    if (empty($playstyle) || empty($playstyle['style'])) return '<p class="centertext" style="opacity: 0.6; padding: 20px;">Analysis requires more data.</p>';
    
    // Icon mapping
    $iconMap = [
        'running' => '🏃',
        'crosshair' => '🎯',
        'rifle' => '🎖️',
        'recruit' => '👶',
    ];
    $iconChar = $iconMap[$playstyle['icon'] ?? 'rifle'] ?? '🎖️';
    
    return '
    <div style="text-align: center; padding: 10px;">
        <div style="font-size: 3em; margin-bottom: 5px;">'.$iconChar.'</div>
        <div style="font-size: 1.5em; font-weight: bold; color: #fff;">'.($playstyle['style'] ?? 'Soldier').'</div>
        <div style="font-size: 0.9em; opacity: 0.7; max-width: 250px; margin: 10px auto;">'.($playstyle['description'] ?? '').'</div>
        <div style="margin-top: 10px; font-size: 0.8em; opacity: 0.5;">Confidence: '.number_format($playstyle['confidence'] ?? 0).'%</div>
    </div>';
}

function template_war_room_rank_icon(int $kills): string
{
    if ($kills >= 100000) return '👑';
    if ($kills >= 50000) return '🏆';
    if ($kills >= 10000) return '💎';
    if ($kills >= 5000) return '🥇';
    if ($kills >= 1000) return '🥈';
    if ($kills >= 100) return '🥉';
    return '🎖️';
}

function template_war_room_weapon_icon(string $weapon): string
{
    $icons = [
        'Thompson' => '🔫', 'MP40' => '🔫', 'Kar98k' => '🎯',
        'Springfield' => '🎯', 'M1 Garand' => '🔫', 'BAR' => '🔫',
        'StG44' => '🔫', 'Grenade' => '💣', 'Knife' => '🔪', 'Pistol' => '🔫',
    ];
    return $icons[$weapon] ?? '🔫';
}

/**
 * Distance breakdown stats
 */
function template_war_room_distance_content($player): string
{
    $walked = ($player['distance_walked'] ?? 0) / 1000;
    $sprinted = ($player['distance_sprinted'] ?? 0) / 1000;
    $swam = ($player['distance_swam'] ?? 0) / 1000;
    $driven = ($player['distance_driven'] ?? 0) / 1000;
    $total = $walked + $sprinted + $swam + $driven;
    
    return '
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
        <div style="text-align: center; padding: 20px; background: rgba(33, 150, 243, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #2196f3;">'.number_format($walked, 1).'</div>
            <div style="opacity: 0.7;">km Walked</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(255, 152, 0, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($sprinted, 1).'</div>
            <div style="opacity: 0.7;">km Sprinted</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(0, 188, 212, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #00bcd4;">'.number_format($swam, 1).'</div>
            <div style="opacity: 0.7;">km Swam</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(156, 39, 176, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #9c27b0;">'.number_format($driven, 1).'</div>
            <div style="opacity: 0.7;">km Driven</div>
        </div>
    </div>
    <div style="text-align: center; font-size: 1.5em; font-weight: bold;">Total: '.number_format($total, 1).' km</div>';
}

/**
 * Jump stats with badge
 */
function template_war_room_jumps_content($player): string
{
    $jumps = $player['jumps'] ?? 0;
    $matches = max(1, $player['matches_played'] ?? 1);
    $badge = $jumps > 1000 ? '<div style="margin-top: 15px; padding: 10px; background: rgba(76, 175, 80, 0.2); border-radius: 8px; font-weight: bold;">🐰 Bunny Hopper!</div>' : '';
    
    return '
    <div style="text-align: center; padding: 30px;">
        <div style="font-size: 4em; font-weight: bold; color: #4caf50;">'.number_format($jumps).'</div>
        <div style="font-size: 1.2em; opacity: 0.8;">Total Jumps</div>
        '.$badge.'
        <div style="margin-top: 20px; font-size: 0.9em; opacity: 0.7;">Avg per match: '.number_format($jumps / $matches, 1).'</div>
    </div>';
}

/**
 * Win/Loss record
 */
function template_war_room_winloss_content($player): string
{
    $wins = $player['wins'] ?? 0;
    $losses = $player['losses'] ?? 0;
    $total = max(1, $wins + $losses);
    $winRate = ($wins / $total) * 100;
    
    return '
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 15px;">
        <div style="text-align: center; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <div style="font-size: 1.8em; font-weight: bold; color: #4caf50;">'.number_format($wins).'</div>
            <div style="opacity: 0.7;">Wins</div>
        </div>
        <div style="text-align: center; padding: 15px; background: rgba(244, 67, 54, 0.1); border-radius: 8px;">
            <div style="font-size: 1.8em; font-weight: bold; color: #f44336;">'.number_format($losses).'</div>
            <div style="opacity: 0.7;">Losses</div>
        </div>
        <div style="text-align: center; padding: 15px; background: rgba(74, 107, 138, 0.1); border-radius: 8px;">
            <div style="font-size: 1.8em; font-weight: bold; color: #4a6b8a;">'.number_format($winRate, 1).'%</div>
            <div style="opacity: 0.7;">Win Rate</div>
        </div>
    </div>';
}

/**
 * Rounds and games breakdown
 */
function template_war_room_rounds_content($player): string
{
    $games = $player['games_played'] ?? 0;
    $rounds = $player['rounds'] ?? 0;
    $playtime = $player['playtime_seconds'] ?? 0;
    $avgLength = format_playtime($playtime / max(1, $games));
    $roundsPerGame = number_format($rounds / max(1, $games), 1);
    
    return '
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>🎮 Games Played</span>
            <strong>'.number_format($games).'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>🔁 Rounds Played</span>
            <strong>'.number_format($rounds).'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <span>⏱️ Avg Game Length</span>
            <strong>'.$avgLength.'</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 15px 0;">
            <span>🎯 Rounds per Game</span>
            <strong>'.$roundsPerGame.'</strong>
        </div>
    </div>';
}

/**
 * Objectives breakdown
 */
function template_war_room_objectives_content($player): string
{
    $objectives = $player['objectives_completed'] ?? 0;
    $flags = $player['flags_captured'] ?? 0;
    $planted = $player['bombs_planted'] ?? 0;
    $defused = $player['bombs_defused'] ?? 0;
    
    return '
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; padding: 20px;">
        <div style="text-align: center; padding: 20px; background: rgba(33, 150, 243, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #2196f3;">'.number_format($objectives).'</div>
            <div style="opacity: 0.7;">Objectives Done</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #4caf50;">'.number_format($flags).'</div>
            <div style="opacity: 0.7;">Flags Captured</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(255, 152, 0, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($planted).'</div>
            <div style="opacity: 0.7;">Bombs Planted</div>
        </div>
        <div style="text-align: center; padding: 20px; background: rgba(156, 39, 176, 0.1); border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: #9c27b0;">'.number_format($defused).'</div>
            <div style="opacity: 0.7;">Bombs Defused</div>
        </div>
    </div>';
}
