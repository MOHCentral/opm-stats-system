<?php
/**
 * MOHAA Login Tokens Templates
 *
 * @package MohaaLoginTokens
 * @version 1.0.0
 */

/**
 * Token generation page template
 */
function template_mohaa_token_generate()
{
    global $context, $txt, $scripturl;

    $tokenData = $context['mohaa_token'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🎮 ', $txt['mohaa_game_login'], '</h3>
    </div>
    
    <div class="windowbg">
        <div class="mohaa-login-intro">
            <h4>', $txt['mohaa_login_instructions_title'], '</h4>
            <p>', $txt['mohaa_login_instructions'], '</p>
            
            <div class="mohaa-login-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <strong>', $txt['mohaa_step1_title'], '</strong>
                        <p>', $txt['mohaa_step1_desc'], '</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <strong>', $txt['mohaa_step2_title'], '</strong>
                        <p>', $txt['mohaa_step2_desc'], '</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <strong>', $txt['mohaa_step3_title'], '</strong>
                        <p>', $txt['mohaa_step3_desc'], '</p>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    // Token display section
    echo '
    <div class="cat_bar">
        <h4 class="catbg">', $txt['mohaa_your_login_token'], '</h4>
    </div>
    
    <div class="windowbg mohaa-token-section">';

    if (!empty($tokenData['new'])) {
        // Newly generated token
        $token = $tokenData['new']['token'];
        $expires = $tokenData['new']['expires_date'];
        $timeRemaining = $expires - time();

        echo '
        <div class="mohaa-token-display new-token">
            <div class="token-success-icon">✓</div>
            <h3>', $txt['mohaa_token_generated'], '</h3>
            <div class="token-value-container">
                <code class="token-value" id="loginToken">', htmlspecialchars($token), '</code>
                <button type="button" class="button" onclick="copyToken()">', $txt['mohaa_copy'], '</button>
            </div>
            <div class="token-expires">
                <span class="warning">⏰ ', sprintf($txt['mohaa_expires_in'], '<span id="countdown">' . floor($timeRemaining / 60) . ':' . str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) . '</span>'), '</span>
            </div>
            <div class="token-command">
                <p>', $txt['mohaa_use_command'], ':</p>
                <code>/login ', htmlspecialchars($token), '</code>
            </div>
        </div>
        
        <script>
            function copyToken() {
                const token = document.getElementById("loginToken").textContent;
                navigator.clipboard.writeText(token).then(() => {
                    alert("', $txt['mohaa_token_copied'], '");
                });
            }
            
            // Countdown timer
            let remaining = ', $timeRemaining, ';
            setInterval(function() {
                remaining--;
                if (remaining <= 0) {
                    document.getElementById("countdown").textContent = "', $txt['mohaa_expired'], '";
                    return;
                }
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                document.getElementById("countdown").textContent = mins + ":" + (secs < 10 ? "0" : "") + secs;
            }, 1000);
        </script>';
    } elseif (!empty($tokenData['existing'])) {
        // Existing active token
        $token = $tokenData['existing']['token'];
        $expires = $tokenData['existing']['expires_date'];
        $timeRemaining = $expires - time();

        echo '
        <div class="mohaa-token-display existing-token">
            <h3>', $txt['mohaa_active_token'], '</h3>
            <div class="token-value-container">
                <code class="token-value" id="loginToken">', htmlspecialchars($token), '</code>
                <button type="button" class="button" onclick="copyToken()">', $txt['mohaa_copy'], '</button>
            </div>
            <div class="token-expires">
                <span>⏰ ', sprintf($txt['mohaa_expires_in'], '<span id="countdown">' . floor($timeRemaining / 60) . ':' . str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) . '</span>'), '</span>
            </div>
            <div class="token-command">
                <p>', $txt['mohaa_use_command'], ':</p>
                <code>/login ', htmlspecialchars($token), '</code>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>', $txt['mohaa_need_new_token'], '</p>
            <form method="post">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                <input type="submit" name="generate_token" value="', $txt['mohaa_generate_new'], '" class="button" />
            </form>
        </div>
        
        <script>
            function copyToken() {
                const token = document.getElementById("loginToken").textContent;
                navigator.clipboard.writeText(token).then(() => {
                    alert("', $txt['mohaa_token_copied'], '");
                });
            }
            
            let remaining = ', $timeRemaining, ';
            setInterval(function() {
                remaining--;
                if (remaining <= 0) {
                    document.getElementById("countdown").textContent = "', $txt['mohaa_expired'], '";
                    return;
                }
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                document.getElementById("countdown").textContent = mins + ":" + (secs < 10 ? "0" : "") + secs;
            }, 1000);
        </script>';
    } else {
        // No token - generate new
        echo '
        <div class="mohaa-token-display no-token">
            <div class="no-token-icon">🔑</div>
            <h3>', $txt['mohaa_no_active_token'], '</h3>
            <p>', $txt['mohaa_generate_prompt'], '</p>
            <form method="post">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                <input type="submit" name="generate_token" value="', $txt['mohaa_generate_token'], '" class="button" />
            </form>
        </div>';
    }

    echo '
    </div>';

    // Security notice
    echo '
    <div class="windowbg">
        <div class="mohaa-security-notice">
            <h4>🔒 ', $txt['mohaa_security_title'], '</h4>
            <ul>
                <li>', $txt['mohaa_security_tip1'], '</li>
                <li>', sprintf($txt['mohaa_security_tip2'], $tokenData['token_expiry_minutes']), '</li>
                <li>', $txt['mohaa_security_tip3'], '</li>
            </ul>
        </div>
    </div>';

    // Recent sessions
    if (!empty($tokenData['sessions'])) {
        echo '
        <div class="cat_bar">
            <h4 class="catbg">', $txt['mohaa_recent_sessions'], '</h4>
        </div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr class="title_bar">
                        <th>', $txt['mohaa_status'], '</th>
                        <th>', $txt['mohaa_server'], '</th>
                        <th>', $txt['mohaa_login_time'], '</th>
                        <th>', $txt['mohaa_last_seen'], '</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($tokenData['sessions'] as $session) {
            $statusClass = $session['status'] === 'active' ? 'online' : 'offline';

            echo '
                    <tr class="windowbg">
                        <td><span class="session-status ', $statusClass, '">●</span> ', ucfirst($session['status']), '</td>
                        <td>', htmlspecialchars($session['server_ip']), $session['server_port'] ? ':' . $session['server_port'] : '', '</td>
                        <td>', timeformat($session['login_time']), '</td>
                        <td>', timeformat($session['last_seen']), '</td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>';
    }

    echo '
    <style>
        .mohaa-login-intro { padding: 20px; }
        .mohaa-login-steps { display: flex; gap: 20px; margin-top: 20px; }
        .step { flex: 1; display: flex; gap: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px; }
        .step-number { width: 40px; height: 40px; background: #4a5d23; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5em; font-weight: bold; flex-shrink: 0; }
        .step-content strong { display: block; margin-bottom: 5px; }
        .step-content p { margin: 0; color: #666; font-size: 0.9em; }
        
        .mohaa-token-section { text-align: center; padding: 30px; }
        .mohaa-token-display { max-width: 500px; margin: 0 auto; }
        .new-token { border: 2px solid #4ade80; background: #f0fdf4; padding: 25px; border-radius: 12px; }
        .existing-token { border: 2px solid #fbbf24; background: #fffbeb; padding: 25px; border-radius: 12px; }
        .no-token { padding: 40px; }
        .token-success-icon { font-size: 3em; color: #4ade80; margin-bottom: 10px; }
        .no-token-icon { font-size: 3em; margin-bottom: 10px; }
        
        .token-value-container { display: flex; gap: 10px; justify-content: center; align-items: center; margin: 20px 0; }
        .token-value { font-size: 1.2em; padding: 15px 20px; background: #1e293b; color: #4ade80; border-radius: 8px; font-family: monospace; user-select: all; }
        
        .token-expires { color: #b45309; font-weight: bold; margin: 10px 0; }
        .token-expires .warning { color: #dc2626; }
        
        .token-command { margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.05); border-radius: 8px; }
        .token-command code { display: block; font-size: 1.1em; margin-top: 10px; padding: 10px; background: #1e293b; color: #fbbf24; border-radius: 4px; }
        
        .mohaa-security-notice { padding: 15px; background: #fef3c7; border-radius: 8px; }
        .mohaa-security-notice h4 { margin: 0 0 10px; }
        .mohaa-security-notice ul { margin: 0; padding-left: 20px; }
        .mohaa-security-notice li { margin: 5px 0; }
        
        .session-status { font-size: 0.8em; }
        .session-status.online { color: #4ade80; }
        .session-status.offline { color: #9ca3af; }
    </style>';
}

/**
 * Profile token management template
 */
function template_mohaa_profile_token()
{
    global $context, $txt, $scripturl;

    $data = $context['mohaa_profile_token'];

    echo '
    <div class="cat_bar">
        <h3 class="catbg">🔑 ', $txt['mohaa_game_login'], '</h3>
    </div>';

    // Linked identity
    echo '
    <div class="windowbg">
        <h4>', $txt['mohaa_linked_identity'], '</h4>';

    if (!empty($data['linked_guid'])) {
        echo '
        <div class="mohaa-identity-linked">
            <span class="status-verified">✓ ', $txt['mohaa_identity_linked'], '</span>
            <div class="identity-details">
                <strong>GUID:</strong> <code>', htmlspecialchars($data['linked_guid']['player_guid']), '</code><br />
                <strong>', $txt['mohaa_linked_since'], ':</strong> ', timeformat($data['linked_guid']['created_date']), '
            </div>
        </div>';
    } else {
        echo '
        <div class="mohaa-identity-unlinked">
            <span class="status-unlinked">✗ ', $txt['mohaa_identity_not_linked'], '</span>
            <p>', $txt['mohaa_link_prompt'], '</p>
            <a href="', $scripturl, '?action=mohaatoken" class="button">', $txt['mohaa_generate_login_token'], '</a>
        </div>';
    }

    echo '
    </div>';

    // Quick login link
    if ($data['is_own']) {
        echo '
        <div class="windowbg" style="text-align: center;">
            <a href="', $scripturl, '?action=mohaatoken" class="button">', $txt['mohaa_get_login_token'], '</a>
        </div>';
    }

    // API Token (for advanced users)
    if ($data['is_own']) {
        echo '
        <div class="cat_bar">
            <h4 class="catbg">', $txt['mohaa_api_token'], '</h4>
        </div>
        <div class="windowbg">
            <p>', $txt['mohaa_api_token_desc'], '</p>';

        if (!empty($data['api_token'])) {
            echo '
            <div class="mohaa-api-token">
                <code>', htmlspecialchars($data['api_token']['token']), '</code>
                <div style="margin-top: 10px; color: #666;">
                    ', $txt['mohaa_expires'], ': ', timeformat($data['api_token']['expires_date']), '
                </div>
                <form method="post" style="margin-top: 10px;">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                    <input type="submit" name="revoke_api_token" value="', $txt['mohaa_revoke'], '" class="button" onclick="return confirm(\'', $txt['mohaa_revoke_confirm'], '\');" />
                </form>
            </div>';
        } else {
            echo '
            <form method="post">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
                <input type="submit" name="generate_api_token" value="', $txt['mohaa_generate_api_token'], '" class="button" />
            </form>';
        }

        echo '
        </div>';
    }

    // Login history
    if (!empty($data['login_history'])) {
        echo '
        <div class="cat_bar">
            <h4 class="catbg">', $txt['mohaa_token_history'], '</h4>
        </div>
        <div class="windowbg">
            <table class="table_grid" style="width: 100%;">
                <thead>
                    <tr class="title_bar">
                        <th>', $txt['mohaa_action'], '</th>
                        <th>', $txt['mohaa_token'], '</th>
                        <th>', $txt['mohaa_ip'], '</th>
                        <th>', $txt['mohaa_time'], '</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['login_history'] as $log) {
            echo '
                    <tr class="windowbg">
                        <td>', ucfirst($log['action']), '</td>
                        <td><code>', htmlspecialchars($log['token_prefix']), '...</code></td>
                        <td>', htmlspecialchars($log['ip_address'] ?: '-'), '</td>
                        <td>', timeformat($log['log_time']), '</td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>
        </div>';
    }

    echo '
    <style>
        .mohaa-identity-linked { padding: 15px; background: #f0fdf4; border: 1px solid #4ade80; border-radius: 8px; }
        .mohaa-identity-unlinked { padding: 15px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; }
        .status-verified { color: #166534; font-weight: bold; }
        .status-unlinked { color: #92400e; font-weight: bold; }
        .identity-details { margin-top: 10px; }
        .mohaa-api-token code { display: block; padding: 15px; background: #1e293b; color: #4ade80; border-radius: 4px; font-family: monospace; word-break: break-all; }
    </style>';
}
