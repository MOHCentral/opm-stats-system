<?php
/**
 * MOHAA Achievements Templates - Command & Control Aesthetic
 *
 * @package MohaaAchievements
 * @version 1.0.0
 */

/**
 * Achievement list - The Medal Case
 */
function template_mohaa_achievements_list()
{
    global $context, $txt, $scripturl, $user_info;

    $data = $context['mohaa_achievements'];

    // Header with progress
    echo '
    <div class="mohaa-medal-case">
        <div class="case-header">
            <div class="case-title">
                <h2>🎖️ THE MEDAL CASE</h2>
                <span class="subtitle">Combat Distinctions & Dishonourable Discharges</span>
            </div>
            <div class="case-stats">
                <div class="stat-ring" style="--percent:', $data['completion_percent'], ';">
                    <span class="ring-value">', $data['unlocked'], '<small>/', $data['total'], '</small></span>
                </div>
                <div class="stat-points">
                    <span class="points-value">', number_format($data['earned_points']), '</span>
                    <span class="points-label">Achievement Points</span>
                </div>
            </div>
        </div>';

    // Navigation tabs
    echo '
        <div class="medal-tabs">
            <a href="', $scripturl, '?action=mohaachievements" class="tab active">All Achievements</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=recent" class="tab">Recent Unlocks</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=leaderboard" class="tab">Leaderboard</a>
        </div>';

    // Categories
    foreach ($data['categories'] as $catCode => $cat) {
        if (empty($cat['achievements'])) continue;

        $unlockedInCat = count(array_filter($cat['achievements'], fn($a) => $a['is_unlocked']));
        $totalInCat = count($cat['achievements']);
        $tierClass = 'tier-' . $cat['info']['style'];

        echo '
        <div class="medal-category ', $tierClass, '">
            <div class="category-header">
                <h3>', $cat['info']['name'], '</h3>
                <span class="category-progress">', $unlockedInCat, ' / ', $totalInCat, '</span>
            </div>
            <div class="medal-grid">';

        foreach ($cat['achievements'] as $ach) {
            $lockedClass = $ach['is_unlocked'] ? 'unlocked' : 'locked';
            $hiddenClass = ($ach['is_hidden'] && !$ach['is_unlocked']) ? 'hidden' : '';

            echo '
                <div class="medal-card ', $lockedClass, ' ', $hiddenClass, '" data-achievement="', $ach['id_achievement'], '">
                    <div class="medal-icon ', $cat['info']['style'], '">
                        <span class="icon-inner">', template_achievement_icon($ach['icon']), '</span>';

            if ($ach['is_unlocked']) {
                echo '<span class="unlocked-check">✓</span>';
            }

            echo '
                    </div>
                    <div class="medal-info">
                        <h4>', ($ach['is_hidden'] && !$ach['is_unlocked']) ? '???' : htmlspecialchars($ach['name']), '</h4>
                        <p>', ($ach['is_hidden'] && !$ach['is_unlocked']) ? 'Secret Achievement' : htmlspecialchars($ach['description']), '</p>';

            if (!$ach['is_unlocked'] && $ach['requirement_value'] > 1) {
                echo '
                        <div class="medal-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:', $ach['progress_percent'], '%;"></div>
                            </div>
                            <span class="progress-text">', number_format($ach['current_progress']), ' / ', number_format($ach['requirement_value']), '</span>
                        </div>';
            }

            echo '
                        <div class="medal-meta">
                            <span class="points">', $ach['points'] > 0 ? '+' . $ach['points'] : $ach['points'], ' pts</span>';

            if ($ach['is_unlocked']) {
                echo '<span class="unlock-date">', timeformat($ach['unlocked_date'], '%b %d, %Y'), '</span>';
            }

            echo '
                        </div>
                    </div>
                </div>';
        }

        echo '
            </div>
        </div>';
    }

    echo '
    </div>';

    template_achievement_styles();
}

/**
 * Achievement icon helper
 */
function template_achievement_icon($icon)
{
    $icons = [
        'medal_bronze' => '🥉',
        'medal_silver' => '🥈',
        'medal_gold' => '🥇',
        'medal_platinum' => '💎',
        'medal_diamond' => '💠',
        'trophy_gold' => '🏆',
        'trophy_platinum' => '👑',
        'headshot' => '🎯',
        'headshot_gold' => '💀',
        'precision' => '⊕',
        'longshot' => '📏',
        'wallbang' => '💥',
        'clutch' => '🔥',
        'streak_10' => '⚡',
        'streak_15' => '⚡⚡',
        'streak_25' => '👹',
        'multi_2' => '2️⃣',
        'multi_3' => '3️⃣',
        'multi_4' => '4️⃣',
        'multi_5' => '☠️',
        'teabag' => '☕',
        'nutshot' => '🥜',
        'backstab' => '🗡️',
        'airshot' => '✈️',
        'denied' => '🚫',
        'camper' => '⛺',
        'prone' => '🐍',
        'jump' => '🐰',
        'blind' => '😎',
        'lowHP' => '❤️‍🩹',
        'shame_deaths' => '💀',
        'shame_fall' => '⬇️',
        'shame_drown' => '🌊',
        'shame_tk' => '🤡',
        'shame_quit' => '🚪',
        'shame_dominated' => '😭',
        'shame_reload' => '🔄',
        'shame_spawn' => '⏱️',
        'map_tourist' => '🗺️',
        'map_traveler' => '🌍',
        'door' => '🚪',
        'door_gold' => '🚪✨',
        'window' => '🪟',
        'ladder' => '🪜',
        'distance' => '🏃',
        'sewer' => '🐀',
        'time_bronze' => '⏰',
        'time_silver' => '⏰',
        'time_gold' => '⏰',
        'time_platinum' => '⏰',
        'time_diamond' => '⏰',
        'founder' => '⭐',
        'early_adopter' => '🌟',
        'secret_pacifist' => '☮️',
        'secret_perfect' => '💯',
        'secret_revenge' => '😈',
        'secret_comeback' => '🔄',
        'weapon_thompson' => '🔫',
        'weapon_kar98' => '🎯',
        'weapon_garand' => '🔫',
        'weapon_mp40' => '🔫',
        'weapon_bar' => '🔫',
        'weapon_stg44' => '🔫',
        'weapon_springfield' => '🎯',
        'grenade' => '💣',
        'pistol' => '🔫',
        'knife' => '🔪',
    ];

    return $icons[$icon] ?? '🎖️';
}

/**
 * Recent achievements
 */
function template_mohaa_achievements_recent()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="mohaa-medal-case">
        <div class="case-header">
            <h2>📜 Recent Achievements</h2>
        </div>
        
        <div class="medal-tabs">
            <a href="', $scripturl, '?action=mohaachievements" class="tab">All Achievements</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=recent" class="tab active">Recent Unlocks</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=leaderboard" class="tab">Leaderboard</a>
        </div>
        
        <div class="recent-feed">';

    foreach ($context['mohaa_recent'] as $ach) {
        echo '
            <div class="feed-item tier-', $ach['tier'], '">
                <div class="feed-icon">', template_achievement_icon($ach['icon']), '</div>
                <div class="feed-content">
                    <div class="feed-title">
                        <a href="', $scripturl, '?action=profile;u=', $ach['id_member'], '">', htmlspecialchars($ach['real_name'] ?: $ach['member_name']), '</a>
                        unlocked <strong>', htmlspecialchars($ach['name']), '</strong>
                    </div>
                    <div class="feed-desc">', htmlspecialchars($ach['description']), '</div>
                    <div class="feed-meta">
                        <span class="points">+', $ach['points'], ' pts</span>
                        <span class="time">', timeformat($ach['unlocked_date']), '</span>
                    </div>
                </div>
            </div>';
    }

    if (empty($context['mohaa_recent'])) {
        echo '<div class="no-data">No recent achievements</div>';
    }

    echo '
        </div>
    </div>';

    template_achievement_styles();
}

/**
 * Leaderboard
 */
function template_mohaa_achievements_leaderboard()
{
    global $context, $txt, $scripturl;

    echo '
    <div class="mohaa-medal-case">
        <div class="case-header">
            <h2>🏆 Achievement Leaderboard</h2>
        </div>
        
        <div class="medal-tabs">
            <a href="', $scripturl, '?action=mohaachievements" class="tab">All Achievements</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=recent" class="tab">Recent Unlocks</a>
            <a href="', $scripturl, '?action=mohaachievements;sa=leaderboard" class="tab active">Leaderboard</a>
        </div>
        
        <table class="achievement-leaderboard">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Achievements</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($context['mohaa_leaderboard'] as $rank => $player) {
        $rankClass = match($rank) {
            0 => 'rank-gold',
            1 => 'rank-silver',
            2 => 'rank-bronze',
            default => '',
        };

        echo '
                <tr class="', $rankClass, '">
                    <td><span class="rank">', ($rank + 1), '</span></td>
                    <td>
                        <a href="', $scripturl, '?action=profile;u=', $player['id_member'], '">', 
                            htmlspecialchars($player['real_name'] ?: $player['member_name']), '
                        </a>
                    </td>
                    <td>', number_format($player['achievement_count']), '</td>
                    <td><strong>', number_format($player['total_points']), '</strong></td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';

    template_achievement_styles();
}

/**
 * Profile medals - Enhanced with links to main system
 */
function template_mohaa_profile_medals()
{
    global $context, $txt, $scripturl;

    $data = $context['mohaa_profile_medals'];
    
    // Load widget template for shared functions
    if (function_exists('template_achievement_badge_icon') === false) {
        // Define inline if widget not loaded
        function template_achievement_badge_icon($icon) {
            $icons = [
                'medal_bronze' => '🥉', 'medal_silver' => '🥈', 'medal_gold' => '🥇',
                'medal_platinum' => '💎', 'medal_diamond' => '💠', 'trophy_gold' => '🏆',
                'trophy_platinum' => '👑', 'headshot' => '🎯', 'headshot_gold' => '💀',
                'clutch' => '🔥', 'streak_10' => '⚡', 'teabag' => '☕', 'grenade' => '💣',
            ];
            return $icons[$icon] ?? '🎖️';
        }
    }

    echo '
    <div class="mohaa-profile-medals-enhanced">
        <div class="profile-header">
            <div class="header-content">
                <h2>🎖️ ', htmlspecialchars($data['member_name']), '\'s Medal Case</h2>
                <div class="header-stats">
                    <div class="stat-box">
                        <span class="stat-value">', $data['count'], '</span>
                        <span class="stat-label">Achievements</span>
                    </div>
                    <div class="stat-box gold">
                        <span class="stat-value">', number_format($data['total_points']), '</span>
                        <span class="stat-label">Points</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-value">';
    
    // Calculate tier based on points
    $tier = 'Bronze';
    if ($data['total_points'] >= 50000) $tier = 'Immortal';
    elseif ($data['total_points'] >= 25000) $tier = 'Legend';
    elseif ($data['total_points'] >= 15000) $tier = 'Champion';
    elseif ($data['total_points'] >= 10000) $tier = 'Grandmaster';
    elseif ($data['total_points'] >= 5000) $tier = 'Master';
    elseif ($data['total_points'] >= 2500) $tier = 'Diamond';
    elseif ($data['total_points'] >= 1000) $tier = 'Platinum';
    elseif ($data['total_points'] >= 500) $tier = 'Gold';
    elseif ($data['total_points'] >= 250) $tier = 'Silver';
    
    echo $tier, '</span>
                        <span class="stat-label">Rank</span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="', $scripturl, '?action=mohaachievements" class="button">📋 All Achievements</a>
                <a href="', $scripturl, '?action=mohaachievements;sa=leaderboard" class="button">🏆 Leaderboard</a>';
    
    if ($data['is_own']) {
        echo '
                <a href="', $scripturl, '?action=mohaachievements;sa=rarest" class="button">💎 Rarest</a>';
    }
    
    echo '
            </div>
        </div>';

    // Featured achievements (highest tier) with glow effects
    if (!empty($data['featured'])) {
        echo '
        <div class="featured-medals">
            <h3>⭐ Featured Distinctions</h3>
            <div class="featured-grid">';

        foreach ($data['featured'] as $ach) {
            $tierColors = [
                1 => '#cd7f32', 2 => '#c0c0c0', 3 => '#ffd700', 4 => '#e5e4e2',
                5 => '#b9f2ff', 6 => '#ff4444', 7 => '#a855f7', 8 => '#f97316'
            ];
            $color = $tierColors[$ach['tier'] ?? 1] ?? '#cd7f32';
            
            echo '
                <a href="', $scripturl, '?action=mohaachievements;sa=view;id=', $ach['id_achievement'] ?? 0, '" 
                   class="featured-medal tier-', $ach['tier'] ?? 1, '">
                    <div class="medal-glow" style="--glow-color:', $color, ';">
                        ', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '
                    </div>
                    <span class="medal-name">', htmlspecialchars($ach['name']), '</span>
                    <span class="medal-points">+', $ach['points'] ?? 0, '</span>
                </a>';
        }

        echo '
            </div>
        </div>';
    }

    // Category breakdown
    $categories = [];
    foreach ($data['achievements'] as $ach) {
        $cat = $ach['category'] ?? 'basic';
        if (!isset($categories[$cat])) {
            $categories[$cat] = ['count' => 0, 'points' => 0, 'achievements' => []];
        }
        $categories[$cat]['count']++;
        $categories[$cat]['points'] += ($ach['points'] ?? 0);
        $categories[$cat]['achievements'][] = $ach;
    }

    $categoryInfo = [
        'basic' => ['name' => 'Basic Training', 'icon' => '🎖️'],
        'weapon' => ['name' => 'Weapon Specialist', 'icon' => '🔫'],
        'tactical' => ['name' => 'Tactical & Skill', 'icon' => '🎯'],
        'humiliation' => ['name' => 'Humiliation', 'icon' => '☕'],
        'shame' => ['name' => 'Hall of Shame', 'icon' => '💀'],
        'map' => ['name' => 'Map Mastery', 'icon' => '🗺️'],
        'dedication' => ['name' => 'Dedication', 'icon' => '⏰'],
        'secret' => ['name' => 'Secret', 'icon' => '🔮'],
        'hitbox' => ['name' => 'Hitbox Mastery', 'icon' => '🎯'],
        'movement' => ['name' => 'Movement Analytics', 'icon' => '🏃'],
        'objective' => ['name' => 'Objective Specialist', 'icon' => '🏁'],
        'physics' => ['name' => 'Physics Pro', 'icon' => '⚛️'],
        'hardcore' => ['name' => 'Hardcore', 'icon' => '💪'],
        'troll' => ['name' => 'Fun / Troll', 'icon' => '🤡'],
        'situational' => ['name' => 'Situational', 'icon' => '⚡'],
    ];

    if (!empty($categories)) {
        echo '
        <div class="category-breakdown">
            <h3>📊 Category Breakdown</h3>
            <div class="category-grid">';

        foreach ($categories as $catCode => $catData) {
            $info = $categoryInfo[$catCode] ?? ['name' => ucfirst($catCode), 'icon' => '🎖️'];
            
            echo '
                <a href="', $scripturl, '?action=mohaachievements;sa=category;cat=', $catCode, '" class="category-card">
                    <span class="cat-icon">', $info['icon'], '</span>
                    <span class="cat-name">', $info['name'], '</span>
                    <span class="cat-count">', $catData['count'], ' unlocked</span>
                    <span class="cat-points">+', number_format($catData['points']), ' pts</span>
                </a>';
        }

        echo '
            </div>
        </div>';
    }

    // All achievements in scrollable list
    echo '
        <div class="all-medals">
            <h3>📜 All Achievements (', $data['count'], ')</h3>
            <div class="medal-list">';

    foreach ($data['achievements'] as $ach) {
        $tierClass = 'tier-' . ($ach['tier'] ?? 1);
        
        echo '
                <a href="', $scripturl, '?action=mohaachievements;sa=view;id=', $ach['id_achievement'] ?? 0, '" 
                   class="medal-row ', $tierClass, '">
                    <span class="medal-icon-small">', template_achievement_badge_icon($ach['icon'] ?? 'medal_bronze'), '</span>
                    <div class="medal-details">
                        <span class="medal-name">', htmlspecialchars($ach['name']), '</span>
                        <span class="medal-desc">', htmlspecialchars($ach['description'] ?? ''), '</span>
                    </div>
                    <span class="medal-date">', timeformat($ach['unlocked_date'], '%b %d, %Y'), '</span>
                    <span class="medal-points">+', $ach['points'] ?? 0, '</span>
                </a>';
    }

    if (empty($data['achievements'])) {
        echo '
            <div class="no-medals">
                <span class="no-medals-icon">🎖️</span>
                <p>No achievements unlocked yet.</p>
                <a href="', $scripturl, '?action=mohaachievements" class="button">Explore Achievements</a>
            </div>';
    }

    echo '
            </div>
        </div>
    </div>';

    template_profile_medals_enhanced_styles();
}

/**
 * Enhanced profile medals styles
 */
function template_profile_medals_enhanced_styles()
{
    echo '
    <style>
        /* ============================================
           PROFILE MEDALS - Enhanced & Linked
           ============================================ */
        
        .mohaa-profile-medals-enhanced {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 16px;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 30px;
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border-bottom: 3px solid #4a5d23;
        }
        
        .profile-header h2 {
            margin: 0 0 15px;
            color: #ffd700;
            font-size: 1.8em;
        }
        
        .header-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }
        
        .stat-box.gold {
            background: rgba(255,215,0,0.1);
            border: 1px solid rgba(255,215,0,0.3);
        }
        
        .stat-box .stat-value {
            display: block;
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
        }
        
        .stat-box.gold .stat-value { color: #ffd700; }
        
        .stat-box .stat-label {
            font-size: 0.8em;
            color: #888;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .header-actions .button {
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid #444;
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .header-actions .button:hover {
            background: rgba(255,255,255,0.2);
            border-color: #4a5d23;
        }
        
        /* Featured Medals */
        .featured-medals {
            padding: 30px;
            background: rgba(0,0,0,0.2);
        }
        
        .featured-medals h3 {
            margin: 0 0 20px;
            color: #ffd700;
            font-size: 1.1em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .featured-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .featured-medal {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            min-width: 120px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .featured-medal:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.1);
        }
        
        .featured-medal.tier-1 { border-color: rgba(205,127,50,0.3); }
        .featured-medal.tier-2 { border-color: rgba(192,192,192,0.3); }
        .featured-medal.tier-3 { border-color: rgba(255,215,0,0.3); }
        .featured-medal.tier-4 { border-color: rgba(229,228,226,0.3); }
        .featured-medal.tier-5 { border-color: rgba(185,242,255,0.3); }
        .featured-medal.tier-6 { border-color: rgba(255,68,68,0.3); }
        .featured-medal.tier-7 { border-color: rgba(168,85,247,0.3); }
        .featured-medal.tier-8 { border-color: rgba(249,115,22,0.3); }
        
        .medal-glow {
            font-size: 3em;
            filter: drop-shadow(0 0 15px var(--glow-color, rgba(255,215,0,0.5)));
            animation: glow-pulse 2s ease-in-out infinite;
        }
        
        @keyframes glow-pulse {
            0%, 100% { filter: drop-shadow(0 0 10px var(--glow-color, rgba(255,215,0,0.3))); }
            50% { filter: drop-shadow(0 0 20px var(--glow-color, rgba(255,215,0,0.6))); }
        }
        
        .featured-medal .medal-name {
            display: block;
            margin-top: 10px;
            font-size: 0.9em;
            color: #fff;
            font-weight: bold;
        }
        
        .featured-medal .medal-points {
            display: block;
            margin-top: 5px;
            font-size: 0.8em;
            color: #ffd700;
        }
        
        /* Category Breakdown */
        .category-breakdown {
            padding: 30px;
            border-bottom: 1px solid #333;
        }
        
        .category-breakdown h3 {
            margin: 0 0 20px;
            color: #888;
            font-size: 1em;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .category-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            border: 1px solid #333;
        }
        
        .category-card:hover {
            background: rgba(255,255,255,0.08);
            border-color: #4a5d23;
        }
        
        .category-card .cat-icon { font-size: 1.5em; margin-bottom: 5px; }
        .category-card .cat-name { font-weight: bold; color: #fff; }
        .category-card .cat-count { font-size: 0.85em; color: #888; }
        .category-card .cat-points { font-size: 0.8em; color: #ffd700; }
        
        /* All Medals List */
        .all-medals {
            padding: 30px;
        }
        
        .all-medals h3 {
            margin: 0 0 15px;
            color: #888;
            font-size: 1em;
        }
        
        .medal-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .medal-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            border-left: 3px solid #333;
        }
        
        .medal-row:hover {
            background: rgba(255,255,255,0.06);
        }
        
        .medal-row.tier-1 { border-left-color: #cd7f32; }
        .medal-row.tier-2 { border-left-color: #c0c0c0; }
        .medal-row.tier-3 { border-left-color: #ffd700; }
        .medal-row.tier-4 { border-left-color: #e5e4e2; }
        .medal-row.tier-5 { border-left-color: #b9f2ff; }
        .medal-row.tier-6 { border-left-color: #ff4444; }
        .medal-row.tier-7 { border-left-color: #a855f7; }
        .medal-row.tier-8 { border-left-color: #f97316; }
        
        .medal-icon-small { font-size: 1.5em; }
        
        .medal-details { flex: 1; min-width: 0; }
        
        .medal-details .medal-name {
            display: block;
            font-weight: bold;
            color: #fff;
        }
        
        .medal-details .medal-desc {
            display: block;
            font-size: 0.8em;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .medal-row .medal-date {
            font-size: 0.8em;
            color: #666;
        }
        
        .medal-row .medal-points {
            font-weight: bold;
            color: #ffd700;
            font-size: 0.9em;
        }
        
        .no-medals {
            text-align: center;
            padding: 40px;
        }
        
        .no-medals-icon {
            font-size: 4em;
            opacity: 0.3;
        }
        
        .no-medals p {
            color: #666;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .header-stats {
                justify-content: center;
            }
            
            .header-actions {
                justify-content: center;
            }
        }
    </style>';
}

/**
 * Achievement styles
 */
function template_achievement_styles()
{
    echo '
    <style>
        /* ============================================
           MEDAL CASE - Command & Control Aesthetic
           ============================================ */
        
        .mohaa-medal-case {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .case-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            border-bottom: 3px solid #4a5d23;
        }
        
        .case-title h2 {
            margin: 0;
            font-size: 2em;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            font-family: "Impact", sans-serif;
            letter-spacing: 2px;
        }
        
        .case-title .subtitle {
            display: block;
            font-size: 0.5em;
            color: #888;
            font-weight: normal;
            letter-spacing: 1px;
        }
        
        .case-stats {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        /* Progress Ring */
        .stat-ring {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(#4a5d23 calc(var(--percent) * 1%), #333 0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .stat-ring::before {
            content: "";
            position: absolute;
            width: 60px;
            height: 60px;
            background: #1a1a2e;
            border-radius: 50%;
        }
        
        .ring-value {
            position: relative;
            z-index: 1;
            font-size: 1.2em;
            font-weight: bold;
            color: #fff;
        }
        
        .ring-value small {
            font-size: 0.6em;
            color: #888;
        }
        
        .stat-points {
            text-align: center;
        }
        
        .points-value {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #ffd700;
        }
        
        .points-label {
            font-size: 0.8em;
            color: #888;
        }
        
        /* Tabs */
        .medal-tabs {
            display: flex;
            background: #0d1b2a;
            border-bottom: 1px solid #333;
        }
        
        .medal-tabs .tab {
            padding: 15px 25px;
            color: #888;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .medal-tabs .tab:hover,
        .medal-tabs .tab.active {
            color: #ffd700;
            border-bottom-color: #4a5d23;
            background: rgba(74, 93, 35, 0.1);
        }
        
        /* Category */
        .medal-category {
            padding: 20px 30px;
            border-bottom: 1px solid #333;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .category-header h3 {
            margin: 0;
            font-size: 1.3em;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        /* Tier colors */
        .tier-bronze .category-header h3 { color: #cd7f32; }
        .tier-silver .category-header h3 { color: #c0c0c0; }
        .tier-gold .category-header h3 { color: #ffd700; }
        .tier-patch .category-header h3 { color: #ff6b6b; }
        .tier-rusty .category-header h3 { color: #8b4513; }
        .tier-stamp .category-header h3 { color: #4ecdc4; }
        .tier-trophy .category-header h3 { color: #a855f7; }
        .tier-secret .category-header h3 { color: #6b7280; }
        
        .category-progress {
            color: #888;
            font-size: 0.9em;
        }
        
        /* Medal Grid */
        .medal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .medal-card {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            border: 1px solid #333;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .medal-card:hover {
            background: rgba(255,255,255,0.08);
            transform: translateY(-2px);
        }
        
        .medal-card.locked {
            opacity: 0.5;
        }
        
        .medal-card.locked:hover {
            opacity: 0.8;
        }
        
        .medal-card.unlocked {
            border-color: #4a5d23;
            box-shadow: 0 0 15px rgba(74, 93, 35, 0.3);
        }
        
        .medal-card.hidden {
            background: repeating-linear-gradient(
                45deg,
                rgba(0,0,0,0.1),
                rgba(0,0,0,0.1) 10px,
                transparent 10px,
                transparent 20px
            );
        }
        
        .medal-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2em;
            position: relative;
            flex-shrink: 0;
        }
        
        .medal-icon.bronze { background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%); }
        .medal-icon.silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); }
        .medal-icon.gold { background: linear-gradient(135deg, #ffd700 0%, #b8860b 100%); }
        .medal-icon.patch { background: linear-gradient(135deg, #ff6b6b 0%, #c0392b 100%); }
        .medal-icon.rusty { background: linear-gradient(135deg, #8b4513 0%, #654321 100%); }
        .medal-icon.stamp { background: linear-gradient(135deg, #4ecdc4 0%, #26a69a 100%); }
        .medal-icon.trophy { background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); }
        .medal-icon.secret { background: linear-gradient(135deg, #6b7280 0%, #374151 100%); }
        
        .unlocked-check {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #4ade80;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            color: white;
        }
        
        .medal-info {
            flex: 1;
            min-width: 0;
        }
        
        .medal-info h4 {
            margin: 0 0 5px;
            font-size: 1em;
            color: #fff;
        }
        
        .medal-info p {
            margin: 0 0 10px;
            font-size: 0.85em;
            color: #888;
            line-height: 1.4;
        }
        
        .medal-progress {
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 6px;
            background: #333;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a5d23, #6b8e23);
            border-radius: 3px;
            transition: width 0.3s;
        }
        
        .progress-text {
            font-size: 0.75em;
            color: #666;
        }
        
        .medal-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8em;
        }
        
        .medal-meta .points {
            color: #ffd700;
            font-weight: bold;
        }
        
        .medal-meta .unlock-date {
            color: #4ade80;
        }
        
        /* Recent Feed */
        .recent-feed {
            padding: 20px 30px;
        }
        
        .feed-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        
        .feed-icon {
            font-size: 2em;
        }
        
        .feed-content {
            flex: 1;
        }
        
        .feed-title {
            margin-bottom: 5px;
        }
        
        .feed-title a {
            color: #4ecdc4;
        }
        
        .feed-desc {
            color: #888;
            font-size: 0.9em;
        }
        
        .feed-meta {
            margin-top: 8px;
            font-size: 0.8em;
            color: #666;
        }
        
        .feed-meta .points {
            color: #ffd700;
            margin-right: 15px;
        }
        
        /* Leaderboard */
        .achievement-leaderboard {
            width: 100%;
            border-collapse: collapse;
        }
        
        .achievement-leaderboard th {
            padding: 15px;
            background: #0d1b2a;
            text-align: left;
            border-bottom: 2px solid #4a5d23;
            color: #ffd700;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }
        
        .achievement-leaderboard td {
            padding: 15px;
            border-bottom: 1px solid #333;
        }
        
        .achievement-leaderboard tr:hover {
            background: rgba(255,255,255,0.03);
        }
        
        .rank-gold td { background: rgba(255, 215, 0, 0.1); }
        .rank-silver td { background: rgba(192, 192, 192, 0.1); }
        .rank-bronze td { background: rgba(205, 127, 50, 0.1); }
        
        .rank {
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .rank-gold .rank { color: #ffd700; }
        .rank-silver .rank { color: #c0c0c0; }
        .rank-bronze .rank { color: #cd7f32; }
        
        /* Profile Medals */
        .mohaa-profile-medals {
            background: #1a1a2e;
            border-radius: 12px;
            overflow: hidden;
            color: #e0e0e0;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            background: linear-gradient(135deg, #0f3460, #16213e);
            border-bottom: 3px solid #4a5d23;
        }
        
        .profile-header h2 {
            margin: 0;
            color: #ffd700;
        }
        
        .header-stats .stat {
            margin-left: 20px;
            color: #888;
        }
        
        .featured-medals {
            padding: 25px;
            background: rgba(0,0,0,0.2);
        }
        
        .featured-medals h3 {
            margin: 0 0 20px;
            color: #ffd700;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.9em;
        }
        
        .featured-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .featured-medal {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            min-width: 100px;
        }
        
        .medal-glow {
            font-size: 3em;
            filter: drop-shadow(0 0 10px rgba(255,215,0,0.5));
            animation: glow 2s ease-in-out infinite;
        }
        
        @keyframes glow {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(255,215,0,0.3)); }
            50% { filter: drop-shadow(0 0 20px rgba(255,215,0,0.6)); }
        }
        
        .featured-medal .medal-name {
            display: block;
            margin-top: 10px;
            font-size: 0.85em;
            color: #888;
        }
        
        .all-medals {
            padding: 25px;
        }
        
        .all-medals h3 {
            margin: 0 0 15px;
            color: #888;
            font-size: 0.9em;
        }
        
        .medal-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #333;
        }
        
        .medal-icon-small {
            font-size: 1.5em;
        }
        
        .medal-row .medal-name {
            flex: 1;
        }
        
        .medal-row .medal-date {
            color: #666;
            font-size: 0.85em;
        }
        
        .medal-row .medal-points {
            color: #ffd700;
            font-weight: bold;
        }
        
        .no-medals, .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>';
}
