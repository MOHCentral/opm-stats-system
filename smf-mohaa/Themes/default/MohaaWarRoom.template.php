<?php
/**
 * MOHAA Enhanced Stats Dashboard - War Room
 * Hybrid Design: Modern Grid Layout + SMF Integration
 *
 * @package MohaaPlayers
 * @version 2.3.0
 */

// Helper function to format playtime (hours and minutes)
if (!function_exists('format_playtime')) {
    function format_playtime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        return $minutes . 'm';
    }
}

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
            <a href="#" onclick="showTab(\'peak\'); return false;" class="mohaa-tab">⚡ Peak</a>
            <a href="#" onclick="showTab(\'combat\'); return false;" class="mohaa-tab active">⚔️ Combat</a>
            <a href="#" onclick="showTab(\'signature\'); return false;" class="mohaa-tab">🎯 Signature</a>
            <a href="#" onclick="showTab(\'weapons\'); return false;" class="mohaa-tab">🔫 Armoury</a>
            <a href="#" onclick="showTab(\'movement\'); return false;" class="mohaa-tab">🏃 Movement</a>
            <a href="#" onclick="showTab(\'gameflow\'); return false;" class="mohaa-tab">🎮 Game</a>
            <a href="#" onclick="showTab(\'interaction\'); return false;" class="mohaa-tab">🗣️ Interaction</a>
            <a href="#" onclick="showTab(\'maps\'); return false;" class="mohaa-tab">🗺️ Maps</a>
            <a href="#" onclick="showTab(\'matches\'); return false;" class="mohaa-tab">📊 Matches</a>
            <a href="#" onclick="showTab(\'achievements\'); return false;" class="mohaa-tab">🏆 Medals</a>
        </div>
        
        <!-- ======================= PEAK PERFORMANCE TAB ======================= -->
        <div id="tab-peak" class="tab-content" style="display: none;" data-lazy="peak" data-loaded="false">
            <div class="lazy-loading-placeholder">
                <div style="text-align: center; padding: 60px;">
                    <div style="font-size: 2em; margin-bottom: 15px;">⏳</div>
                    <div>Loading Peak Performance data...</div>
                </div>
            </div>
        </div>
        
        <!-- ======================= SIGNATURE MOVES TAB ======================= -->
        <div id="tab-signature" class="tab-content" style="display: none;" data-lazy="signature" data-loaded="false">
            <div class="lazy-loading-placeholder">
                <div style="text-align: center; padding: 60px;">
                    <div style="font-size: 2em; margin-bottom: 15px;">⏳</div>
                    <div>Loading Signature Metrics...</div>
                </div>
            </div>
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
                
                <!-- Damage Dealt vs Taken -->
                <div class="windowbg stat-card">
                    <h3>Damage Output</h3>
                    ', template_war_room_damage_content($player), '
                </div>

                <!-- Grenade Stats -->
                <div class="windowbg stat-card" style="grid-column: span 2;">
                    <h3>💣 Grenade Efficiency</h3>
                    ', template_war_room_grenade_content($player), '
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
        
        <!-- ======================= INTERACTION TAB ======================= -->
        <div id="tab-interaction" class="tab-content" style="display: none;">
            ', template_war_room_interaction_content($player), '
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
        window.mohaaData = ' . json_encode($context['mohaa_dashboard'], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE) . ';
        
        document.addEventListener("DOMContentLoaded", function() {
            try {
                initWarRoomCharts();
            } catch (e) {
                console.error("Error initializing charts:", e);
            }
        });
        
        function initWarRoomCharts() {
            const data = window.mohaaData || {};
            if (!data) {
                console.warn("No mohaaData available");
                return;
            }
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
            try {
                // Hide all tabs
                var content = document.getElementsByClassName("tab-content");
                for (var i = 0; i < content.length; i++) {
                    content[i].style.display = "none";
                }
                // Show selected
                var targetTab = document.getElementById("tab-" + tabName);
                if (targetTab) {
                    targetTab.style.display = "block";
                } else {
                    console.error("Tab not found: tab-" + tabName);
                    return;
                }
                
                // Update buttons
                var buttons = document.getElementsByClassName("mohaa-tab");
                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].classList.remove("active");
                    var onclick = buttons[i].getAttribute("onclick");
                    if (onclick && onclick.indexOf(tabName) !== -1) {
                        buttons[i].classList.add("active");
                    }
                }
                
                // Lazy load tab content if needed
                lazyLoadTab(tabName);
            } catch (e) {
                console.error("Error switching tab:", e);
            }
        }
        
        // Lazy loading state
        var loadedTabs = {};
        
        // Lazy load function for Peak and Signature tabs
        function lazyLoadTab(tabName) {
            // Only lazy load specific tabs
            if (tabName !== "peak" && tabName !== "signature") {
                return;
            }
            
            // Skip if already loaded
            if (loadedTabs[tabName]) {
                return;
            }
            
            var tabEl = document.getElementById("tab-" + tabName);
            if (!tabEl) return;
            
            // Mark as loading
            loadedTabs[tabName] = "loading";
            
            // Build the AJAX URL
            var url = "' . $scripturl . '?action=mohaalazyload;tab=" + tabName;
            
            fetch(url)
                .then(function(response) {
                    if (!response.ok) throw new Error("Network error");
                    return response.json();
                })
                .then(function(data) {
                    loadedTabs[tabName] = "loaded";
                    renderLazyTab(tabName, data, tabEl);
                })
                .catch(function(err) {
                    console.error("Failed to load tab:", tabName, err);
                    loadedTabs[tabName] = false; // Allow retry
                    tabEl.innerHTML = "<div style=\"text-align: center; padding: 40px; color: #f44336;\"><div style=\"font-size: 2em;\">⚠️</div><div>Failed to load data. <a href=\"javascript:void(0)\" onclick=\"loadedTabs[\'" + tabName + "\']=false;showTab(\'" + tabName + "\')\">Retry</a></div></div>";
                });
        }
        
        // Render lazy-loaded tab content
        function renderLazyTab(tabName, data, tabEl) {
            if (tabName === "peak") {
                renderPeakTab(data, tabEl);
            } else if (tabName === "signature") {
                renderSignatureTab(data, tabEl);
            }
        }
        
        // Render Peak Performance tab
        function renderPeakTab(data, container) {
            var peak = data.peak_performance || data || {};
            var bestConditions = peak.best_conditions || {};
            
            if (!peak || Object.keys(peak).length === 0) {
                container.innerHTML = "<div style=\"text-align: center; padding: 40px; opacity: 0.7;\"><div style=\"font-size: 3em; margin-bottom: 15px;\">📊</div><div>Peak performance analysis requires more match data.</div></div>";
                return;
            }
            
            var html = "<div class=\"mohaa-grid\">";
            
            // Best Conditions Summary
            html += "<div class=\"windowbg stat-card\" style=\"grid-column: 1 / -1; background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));\">";
            html += "<h3 style=\"text-align: center; margin-bottom: 20px;\">⚡ Your Optimal Conditions</h3>";
            html += "<div style=\"display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;\">";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">🕐</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #4caf50;\">" + (bestConditions.best_hour_label || "Evening") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Time</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">📅</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #2196f3;\">" + (bestConditions.best_day || "Weekend") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Day</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">🗺️</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #ff9800;\">" + (bestConditions.best_map || "Unknown") + "</div>";
            html += "<div style=\"opacity: 0.7;\">Best Map</div></div>";
            
            html += "<div><div style=\"font-size: 2em; margin-bottom: 5px;\">⏱️</div>";
            html += "<div style=\"font-size: 1.8em; font-weight: bold; color: #9c27b0;\">" + (bestConditions.optimal_session_mins || 45) + " min</div>";
            html += "<div style=\"opacity: 0.7;\">Optimal Session</div></div>";
            
            html += "</div></div>"; // End grid, end card
            
            html += "</div>"; // End mohaa-grid
            
            container.innerHTML = html;
        }
        
        // Render Signature Moves tab
        function renderSignatureTab(data, container) {
            var combo = data.combo_metrics || data || {};
            var signature = combo.signature || {};
            var playStyle = signature.play_style || "Soldier";
            
            if (!combo || Object.keys(combo).length === 0) {
                container.innerHTML = "<div style=\"text-align: center; padding: 40px; opacity: 0.7;\"><div style=\"font-size: 3em; margin-bottom: 15px;\">🎯</div><div>Signature analysis requires more gameplay data.</div></div>";
                return;
            }
            
            var icons = {
                "Rusher": "🏃", "Sniper": "🎯", "Tactician": "🧠", "Support": "🛡️",
                "Objective": "🎖️", "Lone Wolf": "🐺", "Aggressor": "⚔️", "All-Rounder": "⭐"
            };
            var icon = icons[playStyle] || "🎖️";
            
            var html = "<div class=\"mohaa-grid\">";
            
            // Play Style Hero Section
            html += "<div class=\"windowbg stat-card\" style=\"grid-column: 1 / -1; text-align: center; background: linear-gradient(135deg, rgba(156, 39, 176, 0.1), rgba(33, 150, 243, 0.1));\">";
            html += "<div style=\"font-size: 5em; margin: 20px 0;\">" + icon + "</div>";
            html += "<div style=\"font-size: 2em; font-weight: bold; text-transform: uppercase;\">" + playStyle + "</div>";
            html += "</div>";
            
            // Stats cards
            var moveCombat = combo.movement_combat || {};
            html += "<div class=\"windowbg stat-card\">";
            html += "<h3>🏃 Movement + Combat</h3>";
            html += renderProgressBar("Run & Gun Index", moveCombat.run_gun_index || 0, "#ff9800");
            html += renderProgressBar("Bunny Hop Efficiency", moveCombat.bunny_hop_efficiency || 0, "#4caf50");
            html += "</div>";
            
            html += "<div class=\"windowbg stat-card\">";
            html += "<h3>🎯 Signature Metrics</h3>";
            html += renderProgressBar("Clutch Rate", signature.clutch_rate || 0, "#f44336");
            html += renderProgressBar("First Blood Rate", signature.first_blood_rate || 0, "#ff5722");
            html += "</div>";
            
            html += "</div>"; // End grid
            
            container.innerHTML = html;
        }
        
        function renderProgressBar(label, value, color) {
            var pct = Math.min(100, Math.max(0, value));
            return "<div style=\"margin-bottom: 12px;\">" +
                "<div style=\"display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;\">" +
                "<span>" + label + "</span><strong style=\"color: " + color + ";\">" + value.toFixed(1) + "</strong></div>" +
                "<div style=\"height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;\">" +
                "<div style=\"width: " + pct + "%; height: 100%; background: " + color + "; border-radius: 4px;\"></div>" +
                "</div></div>";
        }
        
        // Make showTab available globally
        window.showTab = showTab;
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

function template_war_room_damage_content($player) {
    $dealt = $player['damage_dealt'] ?? 0;
    $taken = $player['damage_taken'] ?? 0;
    $max = max($dealt, $taken, 1);
    
    $dealtPct = ($dealt / $max) * 100;
    $takenPct = ($taken / $max) * 100;
    
    return '
    <div style="padding: 15px;">
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold; color: var(--mohaa-success);">Damage Dealt</span>
                <span>'.number_format($dealt).'</span>
            </div>
            <div style="background: rgba(0,0,0,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="background: var(--mohaa-success); height: 100%; width: '.$dealtPct.'%;"></div>
            </div>
        </div>
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold; color: var(--mohaa-danger);">Damage Taken</span>
                <span>'.number_format($taken).'</span>
            </div>
            <div style="background: rgba(0,0,0,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="background: var(--mohaa-danger); height: 100%; width: '.$takenPct.'%;"></div>
            </div>
        </div>
        <div style="margin-top: 15px; font-size: 0.85em; text-align: center; opacity: 0.7;">
            Net: <span style="font-weight: bold; color: '.($dealt >= $taken ? 'var(--mohaa-success)' : 'var(--mohaa-danger)').'">'.($dealt - $taken > 0 ? '+' : '').number_format($dealt - $taken).'</span>
        </div>
    </div>';
}

function template_war_room_special_stats_content($player) {
    $specials = [
        '🚗 Roadkills' => $player['roadkills'] ?? 0,
        '🔨 Bash Kills' => $player['bash_kills'] ?? 0,
        '🤕 Team Kills' => $player['team_kills'] ?? 0,
        '☠️ Suicides' => $player['suicides'] ?? 0,
        '🥜 Nutshots' => $player['nutshots'] ?? 0,
        '🗡️ Backstabs' => $player['backstabs'] ?? 0,
    ];
    
    $html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">';
    foreach ($specials as $label => $val) {
        $html .= '
        <div style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: rgba(0,0,0,0.15); border-radius: 8px;">
            <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 5px;">'.number_format($val).'</div>
            <div style="font-size: 0.85em; opacity: 0.7;">'.$label.'</div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function template_war_room_grenade_content($player) {
    $thrown = $player['grenades_thrown'] ?? 0;
    $kills = $player['grenade_kills'] ?? 0;
    $efficiency = $thrown > 0 ? ($kills / $thrown) * 100 : 0;
    
    return '
    <div style="display: flex; align-items: center; justify-content: space-around; padding: 15px; background: rgba(255, 87, 34, 0.1); border-radius: 8px; border: 1px solid rgba(255, 87, 34, 0.2);">
        <div style="text-align: center;">
            <div style="font-size: 2em;">💣</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($thrown).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Thrown</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($kills).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Kills</div>
        </div>
        <div style="text-align: center;">
            <div style="font-size: 1.5em; font-weight: bold; color: #ff5722;">'.number_format($efficiency, 1).'%</div>
            <div style="font-size: 0.8em; opacity: 0.7;">Efficiency</div>
        </div>
    </div>';
}

function template_war_room_weapons_content($weapons) {
    if (empty($weapons)) return '<p style="padding: 20px; text-align: center; opacity: 0.6;">No weapon data recorded yet.</p>';
    
    $html = '<div class="weapon-list-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">';
    foreach ($weapons as $name => $stats) {
        $icon = template_war_room_weapon_icon($name);
        $kills = $stats['kills'] ?? 0;
        $acc = $stats['accuracy'] ?? 0;
        $headshots = $stats['headshots'] ?? 0;
        $damage = $stats['damage'] ?? 0;
        $shots = $stats['shots'] ?? 0;
        
        // Mastery Logic
        if ($kills >= 1000) { $tier = 'legend'; $color = '#00bcd4'; $badge = '💠'; $rank = 'Legend'; }
        elseif ($kills >= 500) { $tier = 'master'; $color = '#e91e63'; $badge = '💎'; $rank = 'Master'; }
        elseif ($kills >= 200) { $tier = 'expert'; $color = '#ffd700'; $badge = '🥇'; $rank = 'Expert'; }
        elseif ($kills >= 50) { $tier = 'regular'; $color = '#c0c0c0'; $badge = '🥈'; $rank = 'Soldier'; }
        else { $tier = 'rookie'; $color = '#cd7f32'; $badge = '🥉'; $rank = 'Rookie'; }
        
        $progress = min(100, ($kills / ($tier == 'legend' ? 2000 : ($tier == 'master' ? 1000 : ($tier == 'expert' ? 500 : ($tier == 'regular' ? 200 : 50))))) * 100);
        $hsPct = $kills > 0 ? ($headshots / $kills) * 100 : 0;
        
        $html .= '
        <div class="weapon-card" style="background: rgba(255,255,255,0.05); border: 1px solid '.$color.'; border-left-width: 4px; border-radius: 6px; padding: 15px;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 2em; margin-right: 15px;">'.$icon.'</div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: bold; font-size: 1.1em;">'.htmlspecialchars($name).'</span>
                        <span style="font-size: 0.8em; padding: 2px 8px; border-radius: 10px; background: '.$color.'22; color: '.$color.'; border: 1px solid '.$color.'44;">'.$badge.' '.$rank.'</span>
                    </div>
                    <div style="height: 4px; background: rgba(0,0,0,0.2); margin-top: 5px; border-radius: 2px; overflow: hidden;">
                        <div style="height: 100%; background: '.$color.'; width: '.$progress.'%;"></div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85em;">
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Kills</opacity>
                    <b>'.number_format($kills).'</b>
                </div>
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Accuracy</opacity>
                    <b style="color: '.($acc > 30 ? 'var(--mohaa-success)' : '').'">'.number_format($acc, 1).'%</b>
                </div>
                '.($headshots > 0 ? '
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">Headshots</opacity>
                    <b>'.number_format($headshots).'</b>
                </div>
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                    <opacity style="opacity: 0.7;">HS Rate</opacity>
                    <b>'.number_format($hsPct, 1).'%</b>
                </div>' : '').'
                '.($damage > 0 ? '
                <div style="background: rgba(0,0,0,0.1); padding: 5px 8px; border-radius: 4px; display: flex; justify-content: space-between; grid-column: 1 / -1;">
                    <opacity style="opacity: 0.7;">Damage</opacity>
                    <b>'.number_format($damage).'</b>
                </div>' : '').'
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

function template_war_room_interaction_content($player) {
    $chat = $player['chat_messages'] ?? 0;
    $vUses = $player['vehicle_uses'] ?? 0;
    $tUses = $player['turret_uses'] ?? 0;
    
    // Pickups list
    $pickupsHtml = '';
    $pickups = $player['pickups'] ?? [];
    // Ensure array if flattened poorly
    if (!is_array($pickups)) $pickups = [];
    
    // Pickups comes as array of [item_name, count] structs from Go via PHP conversion
    // Go: []PickupStat{ItemName, Count}. PHP: [['item_name'=>..., 'count'=>...]]
    
    foreach ($pickups as $p) {
        $name = is_array($p) ? ($p['item_name'] ?? 'Item') : 'Item';
        $count = is_array($p) ? ($p['count'] ?? 0) : 0;
        
        $pickupsHtml .= '<div style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid rgba(0,0,0,0.05); background: rgba(0,0,0,0.02); margin-bottom: 5px; border-radius: 4px;">
            <span>'.htmlspecialchars($name).'</span>
            <strong>'.number_format($count).'</strong>
        </div>';
    }
    if (empty($pickupsHtml)) $pickupsHtml = '<div style="opacity: 0.6; padding: 20px;">No pickups recorded.</div>';

    return '
    <div class="mohaa-grid">
        <div class="windowbg stat-card">
            <h3>💬 Chat Stats</h3>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 2.5em; font-weight: bold; color: #9c27b0;">'.number_format($chat).'</div>
                <div style="opacity: 0.7;">Messages Sent</div>
            </div>
        </div>
        
        <div class="windowbg stat-card">
            <h3>🚙 Vehicle & Turret</h3>
            <div style="display: flex; justify-content: space-around; padding: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #ff9800;">'.number_format($vUses).'</div>
                    <div style="opacity: 0.7;">Vehicles</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #607d8b;">'.number_format($tUses).'</div>
                    <div style="opacity: 0.7;">Turrets</div>
                </div>
            </div>
        </div>
        
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📦 Top Pickups</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                '.$pickupsHtml.'
            </div>
        </div>
    </div>';
}

// ============================================================================
// PEAK PERFORMANCE TAB
// Shows when and where the player performs best
// ============================================================================

function template_war_room_peak_performance_content($data) {
    $peak = $data['peak_performance'] ?? [];
    
    if (empty($peak)) {
        return '
        <div class="centertext" style="padding: 40px; opacity: 0.7;">
            <div style="font-size: 3em; margin-bottom: 15px;">📊</div>
            <div>Peak performance analysis requires more match data.</div>
            <div style="font-size: 0.9em; opacity: 0.7;">Play more matches to unlock insights!</div>
        </div>';
    }
    
    $bestConditions = $peak['best_conditions'] ?? [];
    $timeOfDay = $peak['time_of_day'] ?? [];
    $dayOfWeek = $peak['day_of_week'] ?? [];
    $maps = $peak['maps'] ?? [];
    $fatigue = $peak['session_fatigue'] ?? [];
    $momentum = $peak['match_momentum'] ?? [];
    
    // Build hourly K/D data for heatmap
    $hourlyData = [];
    foreach ($timeOfDay as $h) {
        $hourlyData[] = [
            'hour' => $h['hour'] ?? 0,
            'kdr' => $h['kdr'] ?? 0,
            'kills' => $h['kills'] ?? 0,
        ];
    }
    $hourlyJson = json_encode($hourlyData);
    
    // Day of week data
    $dayData = [];
    $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    foreach ($dayOfWeek as $d) {
        $dayData[] = [
            'day' => $dayNames[$d['day_of_week'] ?? 0] ?? 'Unknown',
            'kdr' => $d['kdr'] ?? 0,
            'kills' => $d['kills'] ?? 0,
        ];
    }
    $dayJson = json_encode($dayData);
    
    return '
    <div class="mohaa-grid">
        <!-- Best Conditions Summary Card -->
        <div class="windowbg stat-card" style="grid-column: 1 / -1; background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(33, 150, 243, 0.1));">
            <h3 style="text-align: center; margin-bottom: 20px;">⚡ Your Optimal Conditions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;">
                <div class="peak-stat-box">
                    <div style="font-size: 2em; margin-bottom: 5px;">🕐</div>
                    <div style="font-size: 1.8em; font-weight: bold; color: #4caf50;">'.htmlspecialchars($bestConditions['best_hour_label'] ?? '8PM - 10PM').'</div>
                    <div style="opacity: 0.7;">Best Time to Play</div>
                    <div style="font-size: 0.8em; color: #4caf50;">+'.number_format($bestConditions['hour_kdr_boost'] ?? 0, 0).'% K/D vs Average</div>
                </div>
                <div class="peak-stat-box">
                    <div style="font-size: 2em; margin-bottom: 5px;">📅</div>
                    <div style="font-size: 1.8em; font-weight: bold; color: #2196f3;">'.htmlspecialchars($bestConditions['best_day'] ?? 'Saturday').'</div>
                    <div style="opacity: 0.7;">Best Day</div>
                    <div style="font-size: 0.8em; color: #2196f3;">+'.number_format($bestConditions['day_kdr_boost'] ?? 0, 0).'% K/D vs Average</div>
                </div>
                <div class="peak-stat-box">
                    <div style="font-size: 2em; margin-bottom: 5px;">🗺️</div>
                    <div style="font-size: 1.8em; font-weight: bold; color: #ff9800;">'.htmlspecialchars($bestConditions['best_map'] ?? 'Unknown').'</div>
                    <div style="opacity: 0.7;">Best Map</div>
                    <div style="font-size: 0.8em; color: #ff9800;">'.number_format($bestConditions['map_kdr'] ?? 0, 2).' K/D</div>
                </div>
                <div class="peak-stat-box">
                    <div style="font-size: 2em; margin-bottom: 5px;">⏱️</div>
                    <div style="font-size: 1.8em; font-weight: bold; color: #9c27b0;">'.($bestConditions['optimal_session_mins'] ?? 45).' min</div>
                    <div style="opacity: 0.7;">Optimal Session</div>
                    <div style="font-size: 0.8em; color: #9c27b0;">Before fatigue sets in</div>
                </div>
            </div>
        </div>
        
        <!-- Hourly Performance Chart -->
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>🕐 Performance by Hour</h3>
            <div id="peak-hourly-chart" style="height: 250px;"></div>
        </div>
        
        <!-- Day of Week Chart -->
        <div class="windowbg stat-card">
            <h3>📅 Performance by Day</h3>
            <div id="peak-day-chart" style="height: 200px;"></div>
        </div>
        
        <!-- Session Fatigue -->
        <div class="windowbg stat-card">
            <h3>😓 Session Fatigue Analysis</h3>
            '.template_war_room_fatigue_content($fatigue).'
        </div>
        
        <!-- Match Momentum -->
        <div class="windowbg stat-card">
            <h3>📈 Match Momentum</h3>
            '.template_war_room_momentum_content($momentum).'
        </div>
        
        <!-- Top Maps -->
        <div class="windowbg stat-card">
            <h3>🗺️ Best Maps</h3>
            '.template_war_room_peak_maps_content($maps).'
        </div>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Hourly chart
        var hourlyData = '.$hourlyJson.';
        if (hourlyData.length > 0 && typeof ApexCharts !== "undefined") {
            var hourlyOptions = {
                series: [{
                    name: "K/D Ratio",
                    data: hourlyData.map(h => ({x: h.hour + ":00", y: parseFloat(h.kdr).toFixed(2)}))
                }],
                chart: {
                    type: "heatmap",
                    height: 250,
                    toolbar: { show: false },
                    background: "transparent"
                },
                dataLabels: { enabled: true },
                colors: ["#4caf50"],
                theme: { mode: "dark" },
                xaxis: {
                    labels: { style: { colors: "#aaa" } }
                },
                yaxis: {
                    labels: { style: { colors: "#aaa" } }
                }
            };
            new ApexCharts(document.querySelector("#peak-hourly-chart"), hourlyOptions).render();
        }
        
        // Day of week chart
        var dayData = '.$dayJson.';
        if (dayData.length > 0 && typeof ApexCharts !== "undefined") {
            var dayOptions = {
                series: [{
                    name: "K/D Ratio",
                    data: dayData.map(d => parseFloat(d.kdr))
                }],
                chart: {
                    type: "bar",
                    height: 200,
                    toolbar: { show: false },
                    background: "transparent"
                },
                plotOptions: {
                    bar: { borderRadius: 4, horizontal: false }
                },
                xaxis: {
                    categories: dayData.map(d => d.day),
                    labels: { style: { colors: "#aaa" } }
                },
                yaxis: {
                    labels: { style: { colors: "#aaa" } }
                },
                colors: ["#2196f3"],
                theme: { mode: "dark" }
            };
            new ApexCharts(document.querySelector("#peak-day-chart"), dayOptions).render();
        }
    });
    </script>';
}

function template_war_room_fatigue_content($fatigue) {
    if (empty($fatigue)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Not enough session data.</div>';
    }
    
    $segments = [
        ['label' => 'First 15 min', 'key' => 'first_15_kdr', 'color' => '#4caf50'],
        ['label' => '15-30 min', 'key' => 'mid_15_kdr', 'color' => '#8bc34a'],
        ['label' => '30-60 min', 'key' => 'late_30_kdr', 'color' => '#ff9800'],
        ['label' => '60+ min', 'key' => 'overtime_kdr', 'color' => '#f44336'],
    ];
    
    $html = '<div style="padding: 10px;">';
    foreach ($segments as $s) {
        $kdr = $fatigue[$s['key']] ?? 0;
        $width = min(100, ($kdr / 3) * 100); // Scale to max ~3.0 K/D
        $html .= '
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
                <span>'.$s['label'].'</span>
                <strong style="color: '.$s['color'].'">'.number_format($kdr, 2).' K/D</strong>
            </div>
            <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
                <div style="width: '.$width.'%; height: 100%; background: '.$s['color'].'; border-radius: 4px;"></div>
            </div>
        </div>';
    }
    $html .= '<div style="font-size: 0.8em; opacity: 0.7; margin-top: 10px; text-align: center;">
        Optimal session: '.($fatigue['optimal_session_minutes'] ?? 45).' minutes
    </div>';
    $html .= '</div>';
    
    return $html;
}

function template_war_room_momentum_content($momentum) {
    if (empty($momentum)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Not enough match data.</div>';
    }
    
    $html = '<div style="padding: 10px;">';
    
    // After win/loss stats
    $afterWin = $momentum['kdr_after_win'] ?? 0;
    $afterLoss = $momentum['kdr_after_loss'] ?? 0;
    $streak = $momentum['best_streak_kdr'] ?? 0;
    
    $html .= '
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
        <div style="text-align: center; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">'.number_format($afterWin, 2).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">K/D After Wins</div>
        </div>
        <div style="text-align: center; padding: 15px; background: rgba(244, 67, 54, 0.1); border-radius: 8px;">
            <div style="font-size: 1.5em; font-weight: bold; color: #f44336;">'.number_format($afterLoss, 2).'</div>
            <div style="font-size: 0.8em; opacity: 0.7;">K/D After Losses</div>
        </div>
    </div>';
    
    // Insight
    $diff = $afterWin - $afterLoss;
    $insight = $diff > 0.3 ? '🔥 You thrive on momentum! Keep the wins rolling.' 
                         : ($diff < -0.1 ? '💪 You perform well under pressure after losses.' 
                         : '⚖️ Your performance is consistent regardless of previous results.');
    
    $html .= '<div style="font-size: 0.9em; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 6px; text-align: center;">'.$insight.'</div>';
    $html .= '</div>';
    
    return $html;
}

function template_war_room_peak_maps_content($maps) {
    if (empty($maps)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">No map data available.</div>';
    }
    
    $html = '<div style="padding: 10px;">';
    $count = 0;
    foreach ($maps as $m) {
        if ($count++ >= 5) break;
        $mapName = $m['map'] ?? 'Unknown';
        $kdr = $m['kdr'] ?? 0;
        $matches = $m['matches'] ?? 0;
        $winRate = $m['win_rate'] ?? 0;
        
        $html .= '
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; margin-bottom: 8px; background: rgba(0,0,0,0.05); border-radius: 6px;">
            <div>
                <div style="font-weight: bold;">'.htmlspecialchars($mapName).'</div>
                <div style="font-size: 0.8em; opacity: 0.7;">'.$matches.' matches</div>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold; color: #4caf50;">'.number_format($kdr, 2).' K/D</div>
                <div style="font-size: 0.8em; color: #2196f3;">'.number_format($winRate, 0).'% WR</div>
            </div>
        </div>';
    }
    $html .= '</div>';
    
    return $html;
}

// ============================================================================
// SIGNATURE MOVES TAB  
// Cross-event combo metrics and play style analysis
// ============================================================================

function template_war_room_signature_content($data) {
    $combo = $data['combo_metrics'] ?? [];
    
    if (empty($combo)) {
        return '
        <div class="centertext" style="padding: 40px; opacity: 0.7;">
            <div style="font-size: 3em; margin-bottom: 15px;">🎯</div>
            <div>Signature analysis requires more diverse gameplay data.</div>
            <div style="font-size: 0.9em; opacity: 0.7;">Play more matches to unlock insights!</div>
        </div>';
    }
    
    $moveCombat = $combo['movement_combat'] ?? [];
    $signature = $combo['signature'] ?? [];
    $health = $combo['health_objective'] ?? [];
    $economy = $combo['economy_survival'] ?? [];
    
    // Play style badge
    $playStyle = $signature['play_style'] ?? 'Soldier';
    $playStyleIcon = template_war_room_playstyle_icon($playStyle);
    
    return '
    <div class="mohaa-grid">
        <!-- Play Style Badge (Hero Section) -->
        <div class="windowbg stat-card" style="grid-column: 1 / -1; text-align: center; background: linear-gradient(135deg, rgba(156, 39, 176, 0.1), rgba(33, 150, 243, 0.1));">
            <div style="font-size: 5em; margin: 20px 0;">'.$playStyleIcon.'</div>
            <div style="font-size: 2em; font-weight: bold; text-transform: uppercase;">'.htmlspecialchars($playStyle).'</div>
            <div style="opacity: 0.7; margin: 10px 0; max-width: 400px; margin-left: auto; margin-right: auto;">'.template_war_room_playstyle_desc($playStyle).'</div>
        </div>
        
        <!-- Signature Stats Grid -->
        <div class="windowbg stat-card">
            <h3>🏃 Movement + Combat</h3>
            '.template_war_room_move_combat_content($moveCombat).'
        </div>
        
        <div class="windowbg stat-card">
            <h3>🎯 Signature Metrics</h3>
            '.template_war_room_signature_metrics_content($signature).'
        </div>
        
        <div class="windowbg stat-card">
            <h3>❤️ Health & Objective</h3>
            '.template_war_room_health_obj_content($health).'
        </div>
        
        <div class="windowbg stat-card">
            <h3>💰 Economy & Survival</h3>
            '.template_war_room_economy_content($economy).'
        </div>
        
        <!-- Combo Radar Chart -->
        <div class="windowbg stat-card" style="grid-column: 1 / -1;">
            <h3>📊 Your Combat DNA</h3>
            <div id="signature-radar-chart" style="height: 350px;"></div>
        </div>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof ApexCharts !== "undefined") {
            var radarOptions = {
                series: [{
                    name: "Your Stats",
                    data: [
                        '.($moveCombat['run_gun_index'] ?? 50).',
                        '.($moveCombat['bunny_hop_efficiency'] ?? 50).',
                        '.($signature['clutch_rate'] ?? 50).',
                        '.($health['objective_focus'] ?? 50).',
                        '.($economy['scavenger_score'] ?? 50).',
                        '.($signature['first_blood_rate'] ?? 50).'
                    ]
                }],
                chart: {
                    type: "radar",
                    height: 350,
                    toolbar: { show: false },
                    background: "transparent"
                },
                xaxis: {
                    categories: ["Run & Gun", "Bunny Hop", "Clutch", "Objective", "Scavenger", "First Blood"],
                    labels: { style: { colors: "#aaa", fontSize: "11px" } }
                },
                yaxis: {
                    show: false,
                    min: 0,
                    max: 100
                },
                stroke: { width: 2 },
                fill: { opacity: 0.3 },
                markers: { size: 4 },
                colors: ["#9c27b0"],
                theme: { mode: "dark" }
            };
            new ApexCharts(document.querySelector("#signature-radar-chart"), radarOptions).render();
        }
    });
    </script>';
}

function template_war_room_playstyle_icon($style) {
    $icons = [
        'Rusher' => '🏃',
        'Sniper' => '🎯',
        'Tactician' => '🧠',
        'Support' => '🛡️',
        'Objective' => '🎖️',
        'Lone Wolf' => '🐺',
        'Team Player' => '🤝',
        'Aggressor' => '⚔️',
        'Defender' => '🏰',
        'All-Rounder' => '⭐',
    ];
    return $icons[$style] ?? '🎖️';
}

function template_war_room_playstyle_desc($style) {
    $descs = [
        'Rusher' => 'You lead the charge with aggressive, fast-paced gameplay. High mobility, high risk, high reward.',
        'Sniper' => 'Patient and precise. You pick your shots carefully and dominate from range.',
        'Tactician' => 'Strategic mind. You read the battlefield and position yourself for maximum advantage.',
        'Support' => 'The backbone of the team. You enable others to succeed while holding the line.',
        'Objective' => 'Mission-focused warrior. Wins matter more than kills in your book.',
        'Lone Wolf' => 'Self-reliant and deadly. You work best when carving your own path.',
        'Team Player' => 'Force multiplier. Your presence elevates everyone around you.',
        'Aggressor' => 'Relentless pressure. You keep enemies on their heels with constant aggression.',
        'Defender' => 'Immovable object. You excel at holding positions and denying ground.',
        'All-Rounder' => 'Versatile and adaptable. You excel in any situation thrown at you.',
    ];
    return $descs[$style] ?? 'A skilled warrior with a unique combat style.';
}

function template_war_room_move_combat_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $runGun = $data['run_gun_index'] ?? 0;
    $bunnyHop = $data['bunny_hop_efficiency'] ?? 0;
    $slideKills = $data['slide_kill_rate'] ?? 0;
    $airKills = $data['air_kill_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Run & Gun Index', $runGun, '#ff9800', '% of kills while moving').'
        '.template_war_room_progress_bar('Bunny Hop Efficiency', $bunnyHop, '#4caf50', '% accuracy while jumping').'
        '.template_war_room_progress_bar('Slide Kill Rate', $slideKills, '#2196f3', '% of kills while sliding').'
        '.template_war_room_progress_bar('Air Kill Rate', $airKills, '#9c27b0', '% of kills mid-air').'
    </div>';
}

function template_war_room_signature_metrics_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $clutch = $data['clutch_rate'] ?? 0;
    $firstBlood = $data['first_blood_rate'] ?? 0;
    $multiKill = $data['multi_kill_rate'] ?? 0;
    $revenge = $data['revenge_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Clutch Rate', $clutch, '#f44336', '% of 1vX situations won').'
        '.template_war_room_progress_bar('First Blood Rate', $firstBlood, '#ff5722', '% of rounds with first kill').'
        '.template_war_room_progress_bar('Multi-Kill Rate', $multiKill, '#e91e63', '% of kills in multi-kills').'
        '.template_war_room_progress_bar('Revenge Rate', $revenge, '#673ab7', '% of deaths avenged').'
    </div>';
}

function template_war_room_health_obj_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $objFocus = $data['objective_focus'] ?? 0;
    $clutchPlant = $data['clutch_plant_rate'] ?? 0;
    $lowHealth = $data['low_health_kill_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Objective Focus', $objFocus, '#4caf50', '% of rounds with obj action').'
        '.template_war_room_progress_bar('Clutch Plant Rate', $clutchPlant, '#ff9800', '% of last-second plants').'
        '.template_war_room_progress_bar('Low Health Kills', $lowHealth, '#f44336', '% of kills below 25 HP').'
        
        <div style="margin-top: 15px; padding: 10px; background: rgba(76, 175, 80, 0.1); border-radius: 6px; text-align: center;">
            <div style="font-size: 0.8em; opacity: 0.7;">Average HP at Kill</div>
            <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">'.($data['avg_hp_at_kill'] ?? 75).'</div>
        </div>
    </div>';
}

function template_war_room_economy_content($data) {
    if (empty($data)) {
        return '<div style="opacity: 0.6; padding: 20px; text-align: center;">Calculating...</div>';
    }
    
    $scavenger = $data['scavenger_score'] ?? 0;
    $pickupKill = $data['pickup_to_kill_ratio'] ?? 0;
    $survival = $data['survival_rate'] ?? 0;
    
    return '
    <div style="padding: 10px;">
        '.template_war_room_progress_bar('Scavenger Score', $scavenger, '#ff9800', '% efficiency of pickups').'
        '.template_war_room_progress_bar('Survival Rate', $survival, '#4caf50', '% of rounds survived').'
        
        <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="padding: 15px; background: rgba(255, 152, 0, 0.1); border-radius: 6px; text-align: center;">
                <div style="font-size: 0.8em; opacity: 0.7;">Pickup/Kill Ratio</div>
                <div style="font-size: 1.3em; font-weight: bold; color: #ff9800;">'.number_format($pickupKill, 2).'</div>
            </div>
            <div style="padding: 15px; background: rgba(33, 150, 243, 0.1); border-radius: 6px; text-align: center;">
                <div style="font-size: 0.8em; opacity: 0.7;">Avg Lifespan</div>
                <div style="font-size: 1.3em; font-weight: bold; color: #2196f3;">'.($data['avg_lifespan_secs'] ?? 60).'s</div>
            </div>
        </div>
    </div>';
}

function template_war_room_progress_bar($label, $value, $color, $subtext = '') {
    $pct = min(100, max(0, $value));
    return '
    <div style="margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9em; margin-bottom: 4px;">
            <span>'.$label.'</span>
            <strong style="color: '.$color.'">'.number_format($value, 1).'</strong>
        </div>
        <div style="height: 8px; background: rgba(0,0,0,0.1); border-radius: 4px;">
            <div style="width: '.$pct.'%; height: 100%; background: '.$color.'; border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
        '.($subtext ? '<div style="font-size: 0.75em; opacity: 0.6; margin-top: 2px;">'.$subtext.'</div>' : '').'
    </div>';
}
