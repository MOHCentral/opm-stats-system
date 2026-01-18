<?php
/**
 * MOHAA Enhanced Stats Dashboard - War Room
 * Command & Control Aesthetic with Drill-down Tables
 *
 * @package MohaaPlayers
 * @version 2.0.0
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

    echo '
    <div class="mohaa-war-room">
        <!-- Command Header -->
        <div class="war-room-header">
            <div class="operator-identity">
                <div class="operator-rank">', template_war_room_rank_icon($player['kills'] ?? 0), '</div>
                <div class="operator-info">
                    <h1>', htmlspecialchars($member['real_name'] ?? $member['member_name'] ?? 'Soldier'), '</h1>
                    <div class="operator-tags">
                        <span class="clan-tag">[', htmlspecialchars($player['clan_tag'] ?? 'N/A'), ']</span>
                        <span class="elo-badge">ELO: <strong>', number_format($player['elo'] ?? 1000), '</strong></span>
                    </div>
                </div>
            </div>
            <div class="quick-stats">
                <div class="quick-stat">
                    <span class="qs-value odometer">', number_format($player['kills'] ?? 0), '</span>
                    <span class="qs-label">KILLS</span>
                </div>
                <div class="quick-stat deaths">
                    <span class="qs-value">', number_format($player['deaths'] ?? 0), '</span>
                    <span class="qs-label">DEATHS</span>
                </div>
                <div class="quick-stat">
                    <span class="qs-value">', number_format(($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1), 2), '</span>
                    <span class="qs-label">K/D RATIO</span>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="war-room-tabs">
            <button class="tab active" data-section="combat">⚔️ Combat</button>
            <button class="tab" data-section="weapons">🔫 Armoury</button>
            <button class="tab" data-section="tactical">🎯 Tactical</button>
            <button class="tab" data-section="maps">🗺️ Maps</button>
            <button class="tab" data-section="matches">📊 Matches</button>
        </div>

        <!-- Combat Section -->
        <div class="war-room-section active" id="section-combat">
            <div class="section-grid">';

    // KDR Gauge
    template_war_room_kdr_gauge($player);

    // Human Silhouette
    template_war_room_silhouette($player);

    // Accuracy Target
    template_war_room_accuracy($player, $data);

    // Kill Streaks
    template_war_room_streaks($player);

    // Multi-kills
    template_war_room_multikills($player);

    // Special Stats
    template_war_room_special_stats($player);

    echo '
            </div>
        </div>

        <!-- Weapons Section -->
        <div class="war-room-section" id="section-weapons">
            <div class="armoury-grid">';

    template_war_room_weapons($player['weapons'] ?? []);

    echo '
            </div>
        </div>

        <!-- Tactical Section -->
        <div class="war-room-section" id="section-tactical">
            <div class="section-grid">';

    template_war_room_movement($player);
    template_war_room_stance($player);
    template_war_room_rivals($player);

    echo '
            </div>
        </div>

        <!-- Maps Section -->
        <div class="war-room-section" id="section-maps">
            <div class="maps-grid">';

    template_war_room_maps($player['maps'] ?? [], $player);

    echo '
            </div>
        </div>

        <!-- Matches Section -->
        <div class="war-room-section" id="section-matches">
            <h3>Recent Matches</h3>';

    template_war_room_matches($player['recent_matches'] ?? []);

    echo '
        </div>
    </div>';

    template_war_room_styles();
    template_war_room_scripts();
}

/**
 * KDR Gauge Component
 */
function template_war_room_kdr_gauge(array $player): void
{
    global $scripturl;

    $kdr = ($player['kills'] ?? 0) / max(1, $player['deaths'] ?? 1);
    $gaugeOffset = template_war_room_calc_gauge_offset($kdr);

    echo '
    <div class="stat-card gauge-card clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=kdr">
        <h3>Kill/Death Ratio</h3>
        <div class="gauge-container">
            <svg viewBox="0 0 200 120" class="gauge-svg">
                <defs>
                    <linearGradient id="kdr-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#ef4444"/>
                        <stop offset="33%" style="stop-color:#eab308"/>
                        <stop offset="66%" style="stop-color:#22c55e"/>
                        <stop offset="100%" style="stop-color:#ffd700"/>
                    </linearGradient>
                </defs>
                <path class="gauge-bg" d="M20,100 A80,80 0 0,1 180,100"/>
                <path class="gauge-fill" d="M20,100 A80,80 0 0,1 180,100" 
                      style="stroke: url(#kdr-gradient); stroke-dashoffset: ', $gaugeOffset, ';"/>
                <text x="100" y="85" class="gauge-value">', number_format($kdr, 2), '</text>
                <text x="100" y="105" class="gauge-label">K/D</text>
            </svg>
        </div>
        <div class="gauge-legend">
            <span class="bad">0-1</span>
            <span class="ok">1-2</span>
            <span class="good">2-5</span>
            <span class="elite">5+</span>
        </div>
    </div>';
}

/**
 * Human Silhouette Component
 */
function template_war_room_silhouette(array $player): void
{
    $kills = max(1, $player['kills'] ?? 1);
    $headshots = $player['headshots'] ?? 0;
    $torsoKills = $player['torso_kills'] ?? ($kills * 0.4);
    $limbKills = $player['limb_kills'] ?? ($kills * 0.1);

    $headOpacity = min(1, $headshots / $kills);
    $torsoOpacity = min(1, $torsoKills / $kills);
    $limbOpacity = min(1, $limbKills / $kills);

    echo '
    <div class="stat-card silhouette-card">
        <h3>Hit Distribution</h3>
        <div class="silhouette-container">
            <svg viewBox="0 0 100 200" class="human-silhouette">
                <!-- Head -->
                <ellipse cx="50" cy="20" rx="15" ry="18" class="body-part head" 
                         style="fill-opacity: ', $headOpacity, ';"
                         title="Headshots: ', number_format($headshots), '"/>
                <!-- Torso -->
                <path d="M35,40 L65,40 L70,100 L30,100 Z" class="body-part torso"
                      style="fill-opacity: ', $torsoOpacity, ';"
                      title="Torso: ', number_format($torsoKills), '"/>
                <!-- Left Arm -->
                <path d="M30,45 L15,80 L20,82 L35,50 Z" class="body-part limb"/>
                <!-- Right Arm -->
                <path d="M70,45 L85,80 L80,82 L65,50 Z" class="body-part limb"/>
                <!-- Left Leg -->
                <path d="M30,100 L25,175 L35,175 L45,100 Z" class="body-part limb"
                      style="fill-opacity: ', $limbOpacity, ';"/>
                <!-- Right Leg -->
                <path d="M55,100 L65,175 L75,175 L70,100 Z" class="body-part limb"
                      style="fill-opacity: ', $limbOpacity, ';"/>
            </svg>
            <div class="silhouette-stats">
                <div class="sil-stat head-stat">
                    <span class="icon">🎯</span>
                    <span class="value">', number_format($headshots), '</span>
                    <span class="percent">', number_format(($headshots / $kills) * 100, 1), '%</span>
                    <span class="label">Headshots</span>
                </div>
                <div class="sil-stat torso-stat">
                    <span class="icon">💀</span>
                    <span class="value">', number_format($torsoKills), '</span>
                    <span class="percent">', number_format(($torsoKills / $kills) * 100, 1), '%</span>
                    <span class="label">Torso</span>
                </div>
                <div class="sil-stat limb-stat">
                    <span class="icon">🦵</span>
                    <span class="value">', number_format($limbKills), '</span>
                    <span class="percent">', number_format(($limbKills / $kills) * 100, 1), '%</span>
                    <span class="label">Limbs</span>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Accuracy Target Component
 */
function template_war_room_accuracy(array $player, array $data): void
{
    global $scripturl;

    $accuracy = $player['accuracy'] ?? 25;
    $serverAvg = $data['server_avg_accuracy'] ?? 25;
    $crosshairSize = 90 * (1 - $accuracy / 100);

    echo '
    <div class="stat-card target-card clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=accuracy">
        <h3>Accuracy</h3>
        <div class="target-container">
            <svg viewBox="0 0 200 200" class="target-svg">
                <circle cx="100" cy="100" r="90" class="target-ring ring-1"/>
                <circle cx="100" cy="100" r="70" class="target-ring ring-2"/>
                <circle cx="100" cy="100" r="50" class="target-ring ring-3"/>
                <circle cx="100" cy="100" r="30" class="target-ring ring-4"/>
                <circle cx="100" cy="100" r="10" class="target-bullseye"/>
                <!-- Crosshair tightness based on accuracy -->
                <line x1="100" y1="', (100 - $crosshairSize), '" 
                      x2="100" y2="', (100 + $crosshairSize), '" 
                      class="crosshair"/>
                <line x1="', (100 - $crosshairSize), '" y1="100"
                      x2="', (100 + $crosshairSize), '" y2="100"
                      class="crosshair"/>
            </svg>
            <div class="accuracy-value">', number_format($accuracy, 1), '%</div>
        </div>
        <div class="stat-compare">
            <span class="compare-label">Server Avg:</span>
            <span class="compare-value">', number_format($serverAvg, 1), '%</span>
        </div>
    </div>';
}

/**
 * Kill Streaks Component
 */
function template_war_room_streaks(array $player): void
{
    global $scripturl;

    echo '
    <div class="stat-card streaks-card clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=killstreak">
        <h3>Killstreaks</h3>
        <div class="streaks-container">
            <div class="streak-best">
                <span class="streak-icon">🔥</span>
                <span class="streak-value">', $player['best_killstreak'] ?? 0, '</span>
                <span class="streak-label">Best Streak</span>
            </div>
            <div class="streak-breakdown">
                <div class="streak-tier">
                    <span class="tier-name">5 Kills</span>
                    <span class="tier-count">', $player['streaks_5'] ?? 0, 'x</span>
                </div>
                <div class="streak-tier">
                    <span class="tier-name">10 Kills</span>
                    <span class="tier-count">', $player['streaks_10'] ?? 0, 'x</span>
                </div>
                <div class="streak-tier elite">
                    <span class="tier-name">15+ Kills</span>
                    <span class="tier-count">', $player['streaks_15'] ?? 0, 'x</span>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Multi-kills Component
 */
function template_war_room_multikills(array $player): void
{
    global $scripturl;

    echo '
    <div class="stat-card multikill-card">
        <h3>Multi-Kills</h3>
        <div class="multikill-grid">
            <div class="multikill clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=double_kills">
                <span class="mk-skulls">💀💀</span>
                <span class="mk-count">', $player['double_kills'] ?? 0, '</span>
                <span class="mk-label">Double</span>
            </div>
            <div class="multikill clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=triple_kills">
                <span class="mk-skulls">💀💀💀</span>
                <span class="mk-count">', $player['triple_kills'] ?? 0, '</span>
                <span class="mk-label">Triple</span>
            </div>
            <div class="multikill ultra clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=ultra_kills">
                <span class="mk-skulls">☠️</span>
                <span class="mk-count">', $player['ultra_kills'] ?? 0, '</span>
                <span class="mk-label">ULTRA</span>
            </div>
        </div>
    </div>';
}

/**
 * Special Stats Component
 */
function template_war_room_special_stats(array $player): void
{
    global $scripturl;

    $specialStats = [
        ['nutshots', '🥜', 'Nutshots', $player['nutshots'] ?? 0],
        ['backstabs', '🗡️', 'Backstabs', $player['backstabs'] ?? 0],
        ['wallbangs', '💥', 'Wallbangs', $player['wallbangs'] ?? 0],
        ['first_bloods', '🩸', 'First Bloods', $player['first_bloods'] ?? 0],
        ['clutches', '🔥', 'Clutches', $player['clutches'] ?? 0],
        ['revenge_kills', '😈', 'Revenge', $player['revenge_kills'] ?? 0],
    ];

    echo '
    <div class="stat-card special-card">
        <h3>Special Stats</h3>
        <div class="special-grid">';

    foreach ($specialStats as $stat) {
        echo '
            <div class="special-stat clickable" data-url="', $scripturl, '?action=mohaastats;sa=leaderboard;stat=', $stat[0], '">
                <span class="ss-icon">', $stat[1], '</span>
                <span class="ss-value">', number_format($stat[3]), '</span>
                <span class="ss-label">', $stat[2], '</span>
            </div>';
    }

    echo '
        </div>
    </div>';
}

/**
 * Weapons Grid
 */
function template_war_room_weapons(array $weapons): void
{
    global $scripturl;

    foreach ($weapons as $weapon => $wstats) {
        $masteryLevel = template_war_room_weapon_mastery($wstats['kills'] ?? 0);
        $weaponIcon = template_war_room_weapon_icon($weapon);

        echo '
        <div class="weapon-card clickable" data-url="', $scripturl, '?action=mohaastats;sa=weapon;weapon=', urlencode($weapon), '">
            <div class="weapon-icon">', $weaponIcon, '</div>
            <div class="weapon-info">
                <h4>', htmlspecialchars($weapon), '</h4>
                <div class="mastery-tier ', $masteryLevel['class'], '">', $masteryLevel['name'], '</div>
            </div>
            <div class="weapon-stats">
                <div class="wstat">
                    <span class="wstat-value">', number_format($wstats['kills'] ?? 0), '</span>
                    <span class="wstat-label">Kills</span>
                </div>
                <div class="wstat">
                    <span class="wstat-value">', number_format($wstats['accuracy'] ?? 0, 1), '%</span>
                    <span class="wstat-label">Accuracy</span>
                </div>
                <div class="wstat">
                    <span class="wstat-value">', number_format(($wstats['headshots'] ?? 0) / max(1, $wstats['kills'] ?? 1) * 100, 0), '%</span>
                    <span class="wstat-label">HS %</span>
                </div>
            </div>
            <div class="weapon-mastery-bar">
                <div class="mastery-fill" style="width: ', $masteryLevel['progress'], '%;"></div>
            </div>
        </div>';
    }

    if (empty($weapons)) {
        echo '<div class="no-data">No weapon data available</div>';
    }
}

/**
 * Movement Stats
 */
function template_war_room_movement(array $player): void
{
    echo '
    <div class="stat-card movement-card">
        <h3>Movement Profile</h3>
        <div class="movement-stats">
            <div class="mvmt-stat">
                <span class="mvmt-icon">🏃</span>
                <span class="mvmt-value">', number_format(($player['distance_traveled'] ?? 0) / 1000, 1), ' km</span>
                <span class="mvmt-label">Distance</span>
            </div>
            <div class="mvmt-stat">
                <span class="mvmt-icon">🐍</span>
                <span class="mvmt-value">', number_format(($player['prone_time'] ?? 0) / 60, 0), ' min</span>
                <span class="mvmt-label">Prone Time</span>
            </div>
            <div class="mvmt-stat">
                <span class="mvmt-icon">🐰</span>
                <span class="mvmt-value">', number_format($player['jumps'] ?? 0), '</span>
                <span class="mvmt-label">Jumps</span>
            </div>
            <div class="mvmt-stat">
                <span class="mvmt-icon">⛺</span>
                <span class="mvmt-value">', number_format(($player['stationary_time'] ?? 0) / 60, 0), ' min</span>
                <span class="mvmt-label">Camping</span>
            </div>
        </div>
    </div>';
}

/**
 * Stance Stats
 */
function template_war_room_stance(array $player): void
{
    $standingPct = $player['standing_kills_pct'] ?? 50;
    $crouchingPct = $player['crouching_kills_pct'] ?? 30;
    $pronePct = $player['prone_kills_pct'] ?? 20;

    echo '
    <div class="stat-card stance-card">
        <h3>Stance Kill Distribution</h3>
        <div class="stance-bars">
            <div class="stance-bar">
                <span class="stance-icon">🧍</span>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ', $standingPct, '%;"></div>
                </div>
                <span class="stance-value">', $standingPct, '%</span>
            </div>
            <div class="stance-bar">
                <span class="stance-icon">🧎</span>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ', $crouchingPct, '%;"></div>
                </div>
                <span class="stance-value">', $crouchingPct, '%</span>
            </div>
            <div class="stance-bar">
                <span class="stance-icon">🐍</span>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ', $pronePct, '%;"></div>
                </div>
                <span class="stance-value">', $pronePct, '%</span>
            </div>
        </div>
    </div>';
}

/**
 * Rivals (Nemesis & Victim)
 */
function template_war_room_rivals(array $player): void
{
    echo '
    <div class="stat-card nemesis-card">
        <h3>Rivals</h3>
        <div class="rivals-container">
            <div class="rival nemesis">
                <div class="rival-header">
                    <span class="rival-icon">😡</span>
                    <span class="rival-title">NEMESIS</span>
                </div>
                <div class="wanted-poster">
                    <div class="poster-name">', htmlspecialchars($player['nemesis_name'] ?? 'None'), '</div>
                    <div class="poster-stats">Killed you ', $player['nemesis_deaths'] ?? 0, ' times</div>
                </div>
            </div>
            <div class="rival victim">
                <div class="rival-header">
                    <span class="rival-icon">😈</span>
                    <span class="rival-title">VICTIM</span>
                </div>
                <div class="tombstone">
                    <div class="tomb-name">', htmlspecialchars($player['victim_name'] ?? 'None'), '</div>
                    <div class="tomb-stats">You killed them ', $player['victim_kills'] ?? 0, ' times</div>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Maps Grid
 */
function template_war_room_maps(array $maps, array $player): void
{
    global $scripturl;

    foreach ($maps as $mapName => $mstats) {
        $isBest = ($mapName === ($player['best_map'] ?? ''));
        $isWorst = ($mapName === ($player['worst_map'] ?? ''));
        $mapClass = $isBest ? 'best-map' : ($isWorst ? 'worst-map' : '');

        echo '
        <div class="map-card ', $mapClass, ' clickable" data-url="', $scripturl, '?action=mohaastats;sa=map;map=', urlencode($mapName), '">
            <div class="map-image" style="background-image: url(\'/images/maps/', htmlspecialchars($mapName), '.jpg\');">
                <div class="map-overlay">';

        if ($isBest) {
            echo '<span class="map-stamp best">HIGH GROUND</span>';
        } elseif ($isWorst) {
            echo '<span class="map-stamp worst">NO GO ZONE</span>';
        }

        echo '
                </div>
            </div>
            <div class="map-info">
                <h4>', htmlspecialchars($mapName), '</h4>
                <div class="map-stats">
                    <span>K: ', $mstats['kills'] ?? 0, '</span>
                    <span>D: ', $mstats['deaths'] ?? 0, '</span>
                    <span>W: ', $mstats['wins'] ?? 0, '</span>
                </div>
            </div>
        </div>';
    }

    if (empty($maps)) {
        echo '<div class="no-data">No map data available</div>';
    }
}

/**
 * Recent Matches Table
 */
function template_war_room_matches(array $matches): void
{
    echo '
    <table class="matches-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Map</th>
                <th>Result</th>
                <th>K</th>
                <th>D</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($matches as $match) {
        $resultClass = ($match['result'] ?? '') === 'win' ? 'win' : 'loss';
        echo '
            <tr class="', $resultClass, '">
                <td>', timeformat($match['date'] ?? time(), '%b %d'), '</td>
                <td>', htmlspecialchars($match['map'] ?? 'Unknown'), '</td>
                <td><span class="result-badge">', strtoupper($match['result'] ?? 'N/A'), '</span></td>
                <td>', $match['kills'] ?? 0, '</td>
                <td>', $match['deaths'] ?? 0, '</td>
                <td>', $match['score'] ?? 0, '</td>
            </tr>';
    }

    if (empty($matches)) {
        echo '<tr><td colspan="6" class="no-data">No match data available</td></tr>';
    }

    echo '
        </tbody>
    </table>';
}

/**
 * Helper: Calculate gauge offset
 */
function template_war_room_calc_gauge_offset(float $kdr): float
{
    $percent = min(100, ($kdr / 5) * 100);
    $circumference = 251.2;
    return $circumference - ($circumference * $percent / 100);
}

/**
 * Helper: Get rank icon
 */
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

/**
 * Helper: Get weapon mastery level
 */
function template_war_room_weapon_mastery(int $kills): array
{
    if ($kills >= 5000) return ['name' => 'Diamond', 'class' => 'diamond', 'progress' => 100];
    if ($kills >= 2500) return ['name' => 'Platinum', 'class' => 'platinum', 'progress' => 100];
    if ($kills >= 1000) return ['name' => 'Gold', 'class' => 'gold', 'progress' => (($kills - 1000) / 1500) * 100];
    if ($kills >= 500) return ['name' => 'Silver', 'class' => 'silver', 'progress' => (($kills - 500) / 500) * 100];
    if ($kills >= 100) return ['name' => 'Bronze', 'class' => 'bronze', 'progress' => (($kills - 100) / 400) * 100];
    return ['name' => 'Recruit', 'class' => 'recruit', 'progress' => ($kills / 100) * 100];
}

/**
 * Helper: Get weapon icon
 */
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
 * War Room Styles
 */
function template_war_room_styles(): void
{
    echo '
    <style>
        /* ============================================
           WAR ROOM - Command & Control Dashboard
           ============================================ */
        
        .mohaa-war-room {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        /* Header */
        .war-room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border-bottom: 4px solid #4a5d23;
        }
        
        .operator-identity {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .operator-rank {
            font-size: 4em;
            filter: drop-shadow(0 0 15px rgba(255,215,0,0.5));
        }
        
        .operator-info h1 {
            margin: 0;
            font-size: 2em;
            color: #fff;
            font-family: "Impact", sans-serif;
            letter-spacing: 2px;
        }
        
        .operator-tags { margin-top: 5px; }
        
        .clan-tag {
            background: #4a5d23;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-right: 10px;
        }
        
        .elo-badge { color: #ffd700; }
        
        .quick-stats { display: flex; gap: 30px; }
        
        .quick-stat {
            text-align: center;
            padding: 15px 25px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            border: 1px solid #333;
        }
        
        .quick-stat.deaths { opacity: 0.7; }
        
        .qs-value {
            display: block;
            font-size: 2.5em;
            font-weight: bold;
            color: #ffd700;
            font-family: "Courier New", monospace;
        }
        
        .qs-label {
            font-size: 0.75em;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Tabs */
        .war-room-tabs {
            display: flex;
            background: #0d1b2a;
            border-bottom: 1px solid #333;
        }
        
        .war-room-tabs .tab {
            flex: 1;
            padding: 15px;
            border: none;
            background: none;
            color: #888;
            font-size: 1em;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .war-room-tabs .tab:hover,
        .war-room-tabs .tab.active {
            color: #ffd700;
            border-bottom-color: #4a5d23;
            background: rgba(74, 93, 35, 0.1);
        }
        
        /* Sections */
        .war-room-section {
            display: none;
            padding: 25px;
        }
        
        .war-room-section.active { display: block; }
        
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        /* Stat Cards */
        .stat-card {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #333;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            border-color: #4a5d23;
            transform: translateY(-2px);
        }
        
        .stat-card.clickable,
        .clickable { cursor: pointer; }
        
        .stat-card h3 {
            margin: 0 0 15px;
            color: #ffd700;
            font-size: 1em;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        
        /* Gauge */
        .gauge-container { text-align: center; }
        .gauge-svg { width: 200px; height: 120px; }
        
        .gauge-bg {
            fill: none;
            stroke: #333;
            stroke-width: 15;
            stroke-linecap: round;
        }
        
        .gauge-fill {
            fill: none;
            stroke-width: 15;
            stroke-linecap: round;
            stroke-dasharray: 251.2;
            transition: stroke-dashoffset 1s ease-out;
        }
        
        .gauge-value {
            fill: #fff;
            font-size: 2em;
            font-weight: bold;
            text-anchor: middle;
            font-family: "Courier New", monospace;
        }
        
        .gauge-label {
            fill: #888;
            font-size: 0.9em;
            text-anchor: middle;
        }
        
        .gauge-legend {
            display: flex;
            justify-content: space-between;
            font-size: 0.75em;
            color: #666;
            margin-top: 10px;
        }
        
        .gauge-legend .bad { color: #ef4444; }
        .gauge-legend .ok { color: #eab308; }
        .gauge-legend .good { color: #22c55e; }
        .gauge-legend .elite { color: #ffd700; }
        
        /* Silhouette */
        .silhouette-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .human-silhouette { width: 100px; height: 200px; }
        
        .body-part {
            stroke: #fff;
            stroke-width: 0.5;
            transition: fill-opacity 0.3s;
        }
        
        .body-part.head { fill: #ef4444; }
        .body-part.torso { fill: #eab308; }
        .body-part.limb { fill: #3b82f6; }
        
        .silhouette-stats { flex: 1; }
        
        .sil-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        
        .sil-stat .icon { font-size: 1.5em; }
        .sil-stat .value { font-weight: bold; color: #fff; min-width: 60px; }
        .sil-stat .percent { color: #4ade80; font-size: 0.9em; }
        .sil-stat .label { color: #888; margin-left: auto; }
        
        /* Target */
        .target-container { text-align: center; position: relative; }
        .target-svg { width: 150px; height: 150px; }
        
        .target-ring { fill: none; stroke: #333; stroke-width: 2; }
        .target-ring.ring-4 { stroke: #4a5d23; }
        .target-bullseye { fill: #ef4444; }
        .crosshair { stroke: #ffd700; stroke-width: 2; }
        
        .accuracy-value {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #ffd700;
        }
        
        .stat-compare {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        
        .compare-label { color: #888; }
        .compare-value { color: #4ecdc4; margin-left: 5px; }
        
        /* Streaks */
        .streaks-container { text-align: center; }
        .streak-best { margin-bottom: 20px; }
        .streak-icon { font-size: 2em; }
        .streak-value { display: block; font-size: 3em; font-weight: bold; color: #ffd700; }
        .streak-label { color: #888; text-transform: uppercase; font-size: 0.8em; }
        .streak-breakdown { display: flex; gap: 10px; }
        
        .streak-tier {
            flex: 1;
            padding: 10px;
            background: rgba(0,0,0,0.3);
            border-radius: 6px;
            text-align: center;
        }
        
        .streak-tier.elite {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid #ffd700;
        }
        
        .tier-name { display: block; font-size: 0.8em; color: #888; }
        .tier-count { font-weight: bold; color: #fff; }
        
        /* Multi-kills */
        .multikill-grid { display: flex; gap: 15px; }
        
        .multikill {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .multikill:hover { background: rgba(255,255,255,0.05); }
        
        .multikill.ultra {
            background: linear-gradient(135deg, rgba(139,0,0,0.3), rgba(0,0,0,0.3));
            border: 1px solid #8b0000;
        }
        
        .mk-skulls { display: block; font-size: 1.5em; margin-bottom: 5px; }
        .mk-count { display: block; font-size: 1.8em; font-weight: bold; color: #fff; }
        .mk-label { font-size: 0.75em; color: #888; }
        
        /* Special Stats */
        .special-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        
        .special-stat {
            text-align: center;
            padding: 15px 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .special-stat:hover {
            background: rgba(255,255,255,0.05);
            transform: scale(1.05);
        }
        
        .ss-icon { display: block; font-size: 1.8em; margin-bottom: 5px; }
        .ss-value { display: block; font-size: 1.5em; font-weight: bold; color: #fff; }
        .ss-label { font-size: 0.7em; color: #888; }
        
        /* Weapons */
        .armoury-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .weapon-card {
            display: flex;
            flex-direction: column;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #333;
            transition: all 0.3s;
        }
        
        .weapon-card:hover {
            border-color: #4a5d23;
            transform: translateY(-3px);
        }
        
        .weapon-icon { font-size: 3em; text-align: center; margin-bottom: 10px; }
        .weapon-info { text-align: center; }
        .weapon-info h4 { margin: 0 0 5px; color: #fff; }
        
        .mastery-tier {
            font-size: 0.8em;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .mastery-tier.recruit { background: #333; color: #888; }
        .mastery-tier.bronze { background: #cd7f32; color: #fff; }
        .mastery-tier.silver { background: #c0c0c0; color: #333; }
        .mastery-tier.gold { background: #ffd700; color: #333; }
        .mastery-tier.platinum { background: #e5e4e2; color: #333; }
        .mastery-tier.diamond { background: linear-gradient(135deg, #b9f2ff, #00d4ff); color: #333; }
        
        .weapon-stats { display: flex; justify-content: space-around; margin: 15px 0; }
        .wstat { text-align: center; }
        .wstat-value { display: block; font-size: 1.2em; font-weight: bold; color: #ffd700; }
        .wstat-label { font-size: 0.7em; color: #888; }
        
        .weapon-mastery-bar {
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .mastery-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a5d23, #ffd700);
            border-radius: 2px;
        }
        
        /* Movement */
        .movement-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        
        .mvmt-stat {
            text-align: center;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }
        
        .mvmt-icon { display: block; font-size: 2em; margin-bottom: 5px; }
        .mvmt-value { display: block; font-size: 1.3em; font-weight: bold; color: #fff; }
        .mvmt-label { font-size: 0.75em; color: #888; }
        
        /* Stance */
        .stance-bars { display: flex; flex-direction: column; gap: 15px; }
        .stance-bar { display: flex; align-items: center; gap: 10px; }
        .stance-icon { font-size: 1.5em; width: 40px; }
        
        .bar-container {
            flex: 1;
            height: 20px;
            background: #333;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a5d23, #6b8e23);
            border-radius: 10px;
        }
        
        .stance-value { width: 50px; text-align: right; font-weight: bold; color: #fff; }
        
        /* Rivals */
        .rivals-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        
        .rival {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .rival.nemesis {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(0,0,0,0.3));
            border: 1px solid #ef4444;
        }
        
        .rival.victim {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(0,0,0,0.3));
            border: 1px solid #22c55e;
        }
        
        .rival-header { margin-bottom: 15px; }
        .rival-icon { font-size: 2em; }
        .rival-title { display: block; font-size: 0.8em; color: #888; text-transform: uppercase; letter-spacing: 2px; }
        
        .wanted-poster, .tombstone {
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        
        .poster-name, .tomb-name { font-size: 1.2em; font-weight: bold; color: #fff; margin-bottom: 5px; }
        .poster-stats, .tomb-stats { font-size: 0.85em; color: #888; }
        
        /* Maps */
        .maps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .map-card {
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            transition: all 0.3s;
        }
        
        .map-card:hover { transform: scale(1.02); }
        .map-card.best-map { box-shadow: 0 0 20px rgba(34, 197, 94, 0.5); }
        .map-card.worst-map { box-shadow: 0 0 20px rgba(239, 68, 68, 0.5); }
        
        .map-image {
            height: 120px;
            background-size: cover;
            background-position: center;
            background-color: #2a2a4a;
            position: relative;
        }
        
        .map-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .map-stamp {
            padding: 5px 15px;
            font-weight: bold;
            transform: rotate(-15deg);
            border: 3px solid;
            font-size: 0.8em;
        }
        
        .map-stamp.best { border-color: #22c55e; color: #22c55e; background: rgba(0,0,0,0.7); }
        .map-stamp.worst { border-color: #ef4444; color: #ef4444; background: rgba(0,0,0,0.7); }
        
        .map-info { padding: 10px; background: rgba(0,0,0,0.8); }
        .map-info h4 { margin: 0 0 5px; color: #fff; font-size: 0.9em; }
        .map-stats { font-size: 0.75em; color: #888; }
        .map-stats span { margin-right: 10px; }
        
        /* Matches Table */
        .matches-table { width: 100%; border-collapse: collapse; }
        
        .matches-table th {
            padding: 12px;
            background: #0d1b2a;
            text-align: left;
            border-bottom: 2px solid #4a5d23;
            color: #ffd700;
            font-size: 0.85em;
        }
        
        .matches-table td { padding: 12px; border-bottom: 1px solid #333; }
        .matches-table tr:hover { background: rgba(255,255,255,0.03); }
        .matches-table tr.win td:first-child { border-left: 3px solid #22c55e; }
        .matches-table tr.loss td:first-child { border-left: 3px solid #ef4444; }
        
        .result-badge {
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .win .result-badge { background: #22c55e; color: #fff; }
        .loss .result-badge { background: #ef4444; color: #fff; }
        
        .no-data { text-align: center; padding: 40px; color: #666; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .war-room-header { flex-direction: column; gap: 20px; }
            .quick-stats { flex-wrap: wrap; justify-content: center; }
            .silhouette-container { flex-direction: column; }
            .rivals-container { grid-template-columns: 1fr; }
            .multikill-grid { flex-direction: column; }
        }
    </style>';
}

/**
 * War Room Scripts
 */
function template_war_room_scripts(): void
{
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tab switching
            document.querySelectorAll(".war-room-tabs .tab").forEach(function(tab) {
                tab.addEventListener("click", function() {
                    document.querySelectorAll(".war-room-tabs .tab").forEach(t => t.classList.remove("active"));
                    document.querySelectorAll(".war-room-section").forEach(s => s.classList.remove("active"));
                    
                    this.classList.add("active");
                    document.getElementById("section-" + this.dataset.section).classList.add("active");
                });
            });
            
            // Clickable stats (drill-down to leaderboard)
            document.querySelectorAll(".clickable[data-url]").forEach(function(el) {
                el.addEventListener("click", function() {
                    window.location.href = this.dataset.url;
                });
            });
        });
    </script>';
}

/**
 * Stat Leaderboard Drill-down
 */
function template_mohaa_stat_leaderboard()
{
    global $context, $txt, $scripturl;

    $data = $context['mohaa_leaderboard'];

    echo '
    <div class="mohaa-drill-down">
        <div class="drill-header">
            <a href="', $scripturl, '?action=mohaadashboard" class="back-link">← Back to Dashboard</a>
            <h2>', template_war_room_stat_icon($data['stat']), ' ', htmlspecialchars($data['stat_name']), ' Leaderboard</h2>
            <div class="drill-meta">Top 100 Players</div>
        </div>
        
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>', htmlspecialchars($data['stat_name']), '</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($data['players'] as $rank => $player) {
        $rankClass = match($rank) {
            0 => 'gold', 1 => 'silver', 2 => 'bronze', default => ''
        };

        echo '
                <tr class="rank-', $rankClass, '">
                    <td><span class="rank-number">', ($rank + 1), '</span></td>
                    <td><a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">', htmlspecialchars($player['name']), '</a></td>
                    <td><strong>', number_format($player['value'], $data['decimals'] ?? 0), '</strong></td>
                    <td><a href="', $scripturl, '?action=mohaadashboard;u=', $player['id_member'], '" class="profile-btn">View Stats</a></td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';

    template_drill_down_styles();
}

/**
 * Helper: Stat icons
 */
function template_war_room_stat_icon(string $stat): string
{
    $icons = [
        'kills' => '💀', 'deaths' => '⚰️', 'kdr' => '📊',
        'headshots' => '🎯', 'accuracy' => '⊕', 'killstreak' => '🔥',
        'nutshots' => '🥜', 'backstabs' => '🗡️', 'wallbangs' => '💥',
        'first_bloods' => '🩸', 'double_kills' => '💀💀', 'triple_kills' => '💀💀💀',
    ];
    return $icons[$stat] ?? '📈';
}

/**
 * Drill-down styles
 */
function template_drill_down_styles(): void
{
    echo '
    <style>
        .mohaa-drill-down {
            background: #1a1a2e;
            border-radius: 12px;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .drill-header {
            padding: 25px;
            background: linear-gradient(135deg, #0f3460, #16213e);
            border-bottom: 3px solid #4a5d23;
        }
        
        .back-link { color: #4ecdc4; text-decoration: none; font-size: 0.9em; }
        .drill-header h2 { margin: 15px 0 5px; color: #ffd700; }
        .drill-meta { color: #888; }
        
        .leaderboard-table { width: 100%; border-collapse: collapse; }
        
        .leaderboard-table th {
            padding: 15px;
            background: #0d1b2a;
            text-align: left;
            border-bottom: 2px solid #4a5d23;
            color: #ffd700;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        
        .leaderboard-table td { padding: 15px; border-bottom: 1px solid #333; }
        .leaderboard-table tr:hover { background: rgba(255,255,255,0.03); }
        
        .rank-gold td { background: rgba(255, 215, 0, 0.1); }
        .rank-silver td { background: rgba(192, 192, 192, 0.1); }
        .rank-bronze td { background: rgba(205, 127, 50, 0.1); }
        
        .rank-number { font-weight: bold; font-size: 1.2em; }
        .rank-gold .rank-number { color: #ffd700; }
        .rank-silver .rank-number { color: #c0c0c0; }
        .rank-bronze .rank-number { color: #cd7f32; }
        
        .leaderboard-table td a { color: #4ecdc4; text-decoration: none; }
        
        .profile-btn {
            display: inline-block;
            padding: 5px 15px;
            background: #4a5d23;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85em;
        }
        
        .profile-btn:hover { background: #6b8e23; }
    </style>';
}
