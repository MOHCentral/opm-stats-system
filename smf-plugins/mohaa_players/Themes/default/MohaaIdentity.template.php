<?php
/**
 * MOHAA Identity Linking Template
 * 
 * Displays the token for linking in-game identity to forum account.
 *
 * @package MohaaPlayers
 * @version 1.0.0
 */

function template_mohaaidentity()
{
    global $context, $scripturl, $txt;

    echo '
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons members"></span> Link Game Identity
        </h3>
    </div>
    <div class="windowbg">
        <div style="padding: 20px; max-width: 900px; margin: 0 auto;">
            
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin-top: 0;">Connect Your In-Game Soldier</h2>
                <p>Link your in-game identity to your forum account to track your stats, earn achievements, and appear on the leaderboards.</p>
            </div>

            <div style="background: #f0f4f7; border-left: 4px solid #4a90e2; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #333;">Step 1: Get Your Token</h3>
                <p>Use the token below to verify your identity.</p>
                
                <div style="background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace; font-size: 18px; text-align: center; letter-spacing: 2px; margin: 15px 0;">
                    ', $context['mohaa_token'], '
                </div>
                
                <p style="text-align: center; font-size: 12px; color: #666;">This token is permanent and unique to your account.</p>
            </div>

            <div style="background: #e8f5e9; border-left: 4px solid #66bb6a; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #333;">Step 2: Enter Command In-Game</h3>
                <p>Open your MOHAA game console (usually <code>~</code> key) and type the following command:</p>
                
                <div style="background: #222; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 16px; margin: 15px 0;">
                    ', $context['mohaa_console_command'], '
                </div>
            </div>';

    // Pending IP Approvals - show warning if there are any
    if (!empty($context['mohaa_pending_ips'])) {
        echo '
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Pending IP Approval Requests</h3>
                <p>Someone tried to login from a new IP address. Approve if this was you, or deny if it\'s suspicious.</p>
                
                <table class="table_grid" style="margin-top: 15px;">
                    <thead>
                        <tr class="title_bar">
                            <th scope="col">IP Address</th>
                            <th scope="col">Server</th>
                            <th scope="col">Requested</th>
                            <th scope="col">Expires</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($context['mohaa_pending_ips'] as $pending) {
            echo '
                        <tr class="windowbg">
                            <td><strong>', $pending['ip_address'], '</strong></td>
                            <td>', htmlspecialchars($pending['server_name']), '</td>
                            <td>', timeformat($pending['requested_at']), '</td>
                            <td>', timeformat($pending['expires_at']), '</td>
                            <td>
                                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                                    <input type="hidden" name="resolve_pending_ip" value="1">
                                    <input type="hidden" name="approval_id" value="', $pending['id'], '">
                                    <input type="hidden" name="action_type" value="approve">
                                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                                    <button type="submit" class="button" style="background: #28a745; color: white; padding: 5px 10px; font-size: 12px;">
                                        ✓ Approve
                                    </button>
                                </form>
                                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                                    <input type="hidden" name="resolve_pending_ip" value="1">
                                    <input type="hidden" name="approval_id" value="', $pending['id'], '">
                                    <input type="hidden" name="action_type" value="deny">
                                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                                    <button type="submit" class="button" style="background: #dc3545; color: white; padding: 5px 10px; font-size: 12px;">
                                        ✗ Deny
                                    </button>
                                </form>
                            </td>
                        </tr>';
        }

        echo '
                    </tbody>
                </table>
            </div>';
    }

    // Trusted IPs
    echo '
            <div class="cat_bar">
                <h3 class="catbg">🛡️ Trusted IP Addresses</h3>
            </div>
            <p class="description">These IP addresses can automatically reconnect without requiring a new token. Remove any you don\'t recognize.</p>';

    if (!empty($context['mohaa_trusted_ips'])) {
        echo '
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th scope="col">IP Address</th>
                        <th scope="col">Label</th>
                        <th scope="col">Added</th>
                        <th scope="col">Last Used</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($context['mohaa_trusted_ips'] as $trusted) {
            $sourceLabel = $trusted['source'] === 'token_login' ? 'First login' : ($trusted['source'] === 'manual_approval' ? 'Manually approved' : $trusted['source']);
            echo '
                    <tr class="windowbg">
                        <td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">', $trusted['ip_address'], '</code></td>
                        <td>', !empty($trusted['label']) ? htmlspecialchars($trusted['label']) : '<em style="color: #999;">' . $sourceLabel . '</em>', '</td>
                        <td>', timeformat($trusted['created_at']), '</td>
                        <td>', timeformat($trusted['last_used_at']), '</td>
                        <td>
                            <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                                <input type="hidden" name="remove_trusted_ip" value="1">
                                <input type="hidden" name="ip_id" value="', $trusted['id'], '">
                                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                                <button type="submit" class="button" style="background: #dc3545; color: white; padding: 5px 10px; font-size: 12px;" onclick="return confirm(\'Remove this trusted IP? You will need to use a token to login from this IP again.\');">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>';
        }

        echo '
                </tbody>
            </table>';
    } else {
        echo '
            <div class="information" style="margin: 15px 0;">
                No trusted IPs yet. When you successfully login from a game server, that IP will be automatically trusted.
            </div>';
    }

    // Recent Logins
    echo '
            <div class="cat_bar" style="margin-top: 20px;">
                <h3 class="catbg">📋 Recent Login Attempts</h3>
            </div>
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th scope="col">Date</th>
                        <th scope="col">Server</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($context['mohaa_login_history'] as $login) {
        if (strpos($login['status'], 'Success') !== false) {
            $statusStyle = 'color: #155724; background: #d4edda;';
        } elseif (strpos($login['status'], 'trusted_ip_reconnect') !== false) {
            $statusStyle = 'color: #0c5460; background: #d1ecf1;';
        } elseif (strpos($login['status'], 'new_ip_pending') !== false) {
            $statusStyle = 'color: #856404; background: #fff3cd;';
        } else {
            $statusStyle = 'color: #721c24; background: #f8d7da;';
        }
        
        // Clean up status messages for display
        $displayStatus = $login['status'];
        $displayStatus = str_replace('Failed: ', '', $displayStatus);
        $displayStatus = str_replace('_', ' ', $displayStatus);
        $displayStatus = ucfirst($displayStatus);
        
        echo '
                    <tr class="windowbg">
                        <td>', timeformat($login['date']), '</td>
                        <td>', htmlspecialchars($login['server']), '</td>
                        <td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">', $login['ip'], '</code></td>
                        <td><span style="', $statusStyle, ' padding: 3px 8px; border-radius: 3px; font-size: 12px;">', $displayStatus, '</span></td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>

            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                    <input type="hidden" name="regenerate_token" value="1">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="submit" class="button" style="background: #dc3545; color: white;" onclick="return confirm(\'Are you sure you want to regenerate your token?\\n\\nThis will:\\n- Invalidate your current token\\n- Remove ALL trusted IPs\\n- Require you to login again from each location\\n\\nOnly do this if you believe your token has been compromised.\');">
                        🔄 Regenerate Token (Emergency)
                    </button>
                </form>
                <a href="', $scripturl, '?action=mohaa" class="button">Back to War Room</a>
                <br><br>
                <div class="smalltext" style="color: #666;">If you see unrecognized login attempts or trusted IPs, remove them immediately and regenerate your token.</div>
            </div>

        </div>
    </div>';
}
?>
