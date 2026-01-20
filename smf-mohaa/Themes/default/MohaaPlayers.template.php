<?php
/**
 * MOHAA Player Profile Template
 */
function template_mohaa_player_full()
{
    global $context, $txt, $settings, $scripturl;

    $player = $context['mohaa_player'];
    $deep = $player['deep_stats'] ?? [];
    
    // Aesthetic: Dark "Command & Control" variables
    $color_bg = '#1a1a1a';
    $color_panel = '#242424';
    $color_accent = '#4CAF50'; // Military Green
    $color_text = '#e0e0e0';
    $color_muted = '#9e9e9e';
    
    echo '
    <div class="mohaa-profile-container" style="background: '.$color_bg.'; color: '.$color_text.'; padding: 20px; font-family: \'Roboto\', sans-serif;">
        <!-- Header Section -->
        <div class="mohaa-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid '.$color_accent.'; padding-bottom: 15px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center;">
                <div style="width: 80px; height: 80px; background: #333; margin-right: 20px; display: flex; align-items: center; justify-content: center; border: 1px solid #444;">
                    <span style="font-size: 32px; font-weight: bold; color: '.$color_accent.';">'.strtoupper(substr($player['name'], 0, 1)).'</span>
                </div>
                <div>
                    <h1 style="margin: 0; font-size: 28px; color: '.$color_accent.'; text-transform: uppercase; letter-spacing: 1px;">'.$player['name'].'</h1>
                    <div style="color: '.$color_muted.'; font-size: 14px; margin-top: 5px;">GUID: '.substr($player['guid'], 0, 8).'...</div>
                    '.(!empty($player['linked_member']) ? '<div style="margin-top: 5px;"><a href="'.$scripturl.'?action=profile;u='.$player['linked_member']['id_member'].'" style="color: #64B5F6; text-decoration: none;">View Forum Profile</a></div>' : '').'
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 36px; font-weight: bold;">'.number_format($deep['combat']['kd_ratio'] ?? 0, 2).'</div>
                <div style="color: '.$color_muted.'; text-transform: uppercase; font-size: 12px;">K/D Ratio</div>
            </div>
        </div>

        <!-- Metric Grid: Combat Core -->
        <h3 style="border-left: 4px solid '.$color_accent.'; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Combat Telemetry</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Total Kills</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['kills'] ?? 0).'</div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Deaths</div>
                <div style="font-size: 24px; font-weight: bold; color: #F44336;">'.number_format($deep['combat']['deaths'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Headshots</div>
                <div style="font-size: 24px; font-weight: bold; color: #FFC107;">'.number_format($deep['combat']['headshots'] ?? 0).' <span style="font-size: 14px; color: '.$color_muted.';">('.number_format($deep['combat']['headshot_percent'] ?? 0, 1).'%)</span></div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Streak (Best)</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['highest_streak'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Melee Kills</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['combat']['melee_kills'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Suicides</div>
                <div style="font-size: 24px; font-weight: bold; color: #F44336;">'.number_format($deep['combat']['suicides'] ?? 0).'</div>
            </div>
        </div>

        <!-- Movement & Movement -->
        <h3 style="border-left: 4px solid #2196F3; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Movement Analysis</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Distance Traveled</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['movement']['total_distance_km'] ?? 0, 2).' <small>km</small></div>
            </div>
            <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Jumps</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['movement']['jump_count'] ?? 0).'</div>
            </div>
             <div style="background: '.$color_panel.'; padding: 15px; border: 1px solid #333; text-align: center;">
                <div style="color: '.$color_muted.'; font-size: 12px; text-transform: uppercase;">Avg Kill Dist</div>
                <div style="font-size: 24px; font-weight: bold; color: #fff;">'.number_format($deep['accuracy']['avg_distance'] ?? 0, 1).' <small>m</small></div>
            </div>
        </div>

        <!-- Weapon Performance Table -->
        <h3 style="border-left: 4px solid #FFC107; padding-left: 10px; margin-bottom: 15px; text-transform: uppercase;">Weapon Mastery</h3>
        <div style="background: '.$color_panel.'; border: 1px solid #333; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; color: '.$color_text.';">
                <thead>
                    <tr style="background: #111; text-transform: uppercase; font-size: 13px;">
                        <th style="padding: 12px; text-align: left;">Weapon</th>
                        <th style="padding: 12px; text-align: center;">Kills</th>
                        <th style="padding: 12px; text-align: center;">Deaths</th>
                        <th style="padding: 12px; text-align: center;">HS %</th>
                        <th style="padding: 12px; text-align: center;">Acc %</th>
                        <th style="padding: 12px; text-align: center;">Damage</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (!empty($deep['weapons'])) {
        foreach ($deep['weapons'] as $w) {
            $hsPct = $w['kills'] > 0 ? ($w['headshots'] / $w['kills']) * 100 : 0;
            echo '
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 12px; font-weight: bold;">'.htmlspecialchars($w['name']).'</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($w['kills']).'</td>
                        <td style="padding: 12px; text-align: center; color: #F44336;">'.number_format($w['deaths']).'</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($hsPct, 1).'%</td>
                        <td style="padding: 12px; text-align: center;">'.number_format($w['accuracy'], 1).'%</td>
                         <td style="padding: 12px; text-align: center;">'.number_format($w['damage']).'</td>
                    </tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: '.$color_muted.';">No weapon data available.</td></tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>';
}

/**
 * Profile Identity Linking Template
 */
function template_mohaa_profile_identity()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons members"></span> ', $txt['mohaa_link_identity'] ?? 'Link Game Identity', '
        </h3>
    </div>
    <div class="windowbg">';
    
    // Show existing linked identities
    if (!empty($context['mohaa_identities'])) {
        echo '
        <h4>', $txt['mohaa_linked_identities'] ?? 'Linked Game Identities', '</h4>
        <table class="table_grid">
            <thead>
                <tr class="title_bar">
                    <th>Player Name</th>
                    <th>GUID</th>
                    <th>Linked Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($context['mohaa_identities'] as $identity) {
            echo '
                <tr class="windowbg">
                    <td><strong>', htmlspecialchars($identity['player_name']), '</strong></td>
                    <td><code>', htmlspecialchars(substr($identity['player_guid'], 0, 8)), '...</code></td>
                    <td>', timeformat($identity['linked_date']), '</td>
                    <td>
                        <form method="post" action="', $scripturl, '?action=profile;area=mohaaidentity" style="display: inline;">
                            <input type="hidden" name="mohaa_action" value="unlink">
                            <input type="hidden" name="identity_id" value="', $identity['id_identity'], '">
                            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                            <button type="submit" class="button" onclick="return confirm(\'', $txt['mohaa_unlink_confirm'] ?? 'Are you sure you want to unlink this identity?', '\');">
                                ', $txt['mohaa_unlink'] ?? 'Unlink', '
                            </button>
                        </form>
                    </td>
                </tr>';
        }
        
        echo '
            </tbody>
        </table>
        <hr>';
    }
    
    // Show claim code generation
    echo '
        <div style="padding: 20px; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin-top: 0;">', $txt['mohaa_link_new_identity'] ?? 'Link a New Game Identity', '</h2>
                <p>', $txt['mohaa_link_instructions'] ?? 'Generate a claim code and enter it in-game to link your soldier to this account.', '</p>
            </div>';
    
    // Show existing claim code if available
    if (!empty($context['mohaa_claim_code'])) {
        $timeLeft = $context['mohaa_claim_expires'] - time();
        echo '
            <div style="background: #e8f5e9; border-left: 4px solid #66bb6a; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #333;">', $txt['mohaa_your_claim_code'] ?? 'Your Claim Code', '</h3>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-size: 24px; text-align: center; letter-spacing: 4px; margin: 15px 0;">
                    ', htmlspecialchars($context['mohaa_claim_code']), '
                </div>
                <p style="text-align: center; font-size: 14px; color: #666;">
                    ', sprintf($txt['mohaa_code_expires'] ?? 'This code expires in %d minutes.', ceil($timeLeft / 60)), '
                </p>
                <div style="background: #222; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 16px; margin: 15px 0;">
                    /claim ', htmlspecialchars($context['mohaa_claim_code']), '
                </div>
                <p style="text-align: center; font-size: 12px; color: #666;">
                    ', $txt['mohaa_enter_in_console'] ?? 'Enter this command in your game console (press ~ to open).', '
                </p>
            </div>';
    }
    
    // Generate claim code button
    echo '
            <form method="post" action="', $scripturl, '?action=profile;area=mohaaidentity" style="text-align: center;">
                <input type="hidden" name="mohaa_action" value="generate_claim">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                <button type="submit" class="button">
                    ', empty($context['mohaa_claim_code']) ? ($txt['mohaa_generate_code'] ?? 'Generate Claim Code') : ($txt['mohaa_regenerate_code'] ?? 'Generate New Code'), '
                </button>
            </form>
        </div>
    </div>';
}

/**
 * Compare two players
 */
function template_mohaa_compare()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">Player Comparison</h3>
    </div>
    <div class="windowbg">
        <p>Player comparison functionality coming soon.</p>
    </div>';
}

/**
 * Player comparison selection page (when no players selected yet)
 */
function template_mohaa_compare_select()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">', $txt['mohaa_compare_players'] ?? 'Compare Players', '</h3>
    </div>
    <div class="windowbg">
        <form action="', $scripturl, '?action=mohaacompare" method="get" style="max-width: 600px; margin: 0 auto;">
            <input type="hidden" name="action" value="mohaacompare">
            
            <div class="roundframe" style="margin-bottom: 20px;">
                <h4 style="margin-top: 0;">', $txt['mohaa_select_players'] ?? 'Select Two Players to Compare', '</h4>
                
                <div style="margin-bottom: 15px;">
                    <label for="p1" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ', $txt['mohaa_player_1'] ?? 'Player 1', ':
                    </label>
                    <input type="text" name="p1" id="p1" 
                           placeholder="', $txt['mohaa_enter_guid_or_name'] ?? 'Enter GUID or player name', '" 
                           style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="p2" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        ', $txt['mohaa_player_2'] ?? 'Player 2', ':
                    </label>
                    <input type="text" name="p2" id="p2" 
                           placeholder="', $txt['mohaa_enter_guid_or_name'] ?? 'Enter GUID or player name', '" 
                           style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="button">
                        ', $txt['mohaa_compare'] ?? 'Compare', '
                    </button>
                </div>
            </div>
        </form>';
    
    // Show recent players for quick selection
    if (!empty($context['mohaa_recent_players'])) {
        echo '
        <div class="roundframe">
            <h4 style="margin-top: 0;">', $txt['mohaa_recent_players'] ?? 'Recent Players', '</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">';
        
        foreach ($context['mohaa_recent_players'] as $player) {
            echo '
                <a href="', $scripturl, '?action=mohaaplayer;guid=', urlencode($player['guid']), '" class="button" style="font-size: 12px;">
                    ', htmlspecialchars($player['name']), '
                </a>';
        }
        
        echo '
            </div>
        </div>';
    }
    
    echo '
    </div>';
}

/**
 * Profile stats tab - shows MOHAA stats in member profile
 */
function template_mohaa_profile_stats()
{
    global $context, $txt, $scripturl;
    
    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons stats"></span> ', $txt['mohaa_game_stats'] ?? 'Game Statistics', '
        </h3>
    </div>';
    
    // No linked identity
    if (!empty($context['mohaa_no_identity'])) {
        echo '
        <div class="windowbg centertext">
            <p>', $txt['mohaa_no_linked_identity'] ?? 'This member has not linked their game identity yet.', '</p>';
        
        // If viewing own profile, show link option
        if (!empty($context['user']['is_owner'])) {
            echo '
            <p>
                <a href="', $scripturl, '?action=profile;area=mohaaidentity" class="button">
                    ', $txt['mohaa_link_identity'] ?? 'Link Your Game Identity', '
                </a>
            </p>';
        }
        
        echo '
        </div>';
        return;
    }
    
    // Show player stats
    $stats = $context['mohaa_profile_stats'] ?? [];
    $player = $stats['player'] ?? [];
    
    echo '
    <div class="windowbg">';
    
    if (!empty($player)) {
        // Quick stats grid
        echo '
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #4caf50;">
                    ', number_format($player['kills'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_kills'] ?? 'Kills', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #f44336;">
                    ', number_format($player['deaths'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_deaths'] ?? 'Deaths', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['kd_ratio'] ?? 0, 2), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_kd_ratio'] ?? 'K/D Ratio', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold; color: #ff9800;">
                    ', number_format($player['headshots'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_headshots'] ?? 'Headshots', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['playtime_hours'] ?? 0, 1), 'h
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_playtime'] ?? 'Playtime', '
                </div>
            </div>
            <div class="roundframe" style="text-align: center; padding: 15px;">
                <div style="font-size: 24px; font-weight: bold;">
                    ', number_format($player['matches'] ?? 0), '
                </div>
                <div style="font-size: 12px; color: #888; text-transform: uppercase;">
                    ', $txt['mohaa_matches'] ?? 'Matches', '
                </div>
            </div>
        </div>';
        
        // Link to full stats
        if (!empty($player['guid'])) {
            echo '
            <div style="text-align: center;">
                <a href="', $scripturl, '?action=mohaaplayer;guid=', urlencode($player['guid']), '" class="button">
                    ', $txt['mohaa_view_full_stats'] ?? 'View Full Stats', '
                </a>
            </div>';
        }
    } else {
        // No stats yet - show helpful message and GUID
        $stats = $context['mohaa_profile_stats'] ?? [];
        $guid = $stats['guid'] ?? '';
        
        echo '
        <div class="roundframe centertext" style="padding: 30px;">
            <p style="margin-top: 0; font-size: 16px; font-weight: bold;">
                ', $txt['mohaa_no_stats_available'] ?? 'No stats available yet.', '
            </p>';
        
        if (!empty($guid)) {
            echo '
            <p style="color: #888; margin: 10px 0;">
                <strong>Linked GUID:</strong> <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">', $guid, '</code>
            </p>
            <p style="color: #888; margin-bottom: 15px; font-size: 13px;">
                Stats will appear here once you play on a connected OpenMOHAA server.
            </p>';
        }
        
        echo '
        </div>';
    }
    
    echo '
    </div>';
}
?>
