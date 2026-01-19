<?php
/**
 * MOHAA Identity Linking Template
 * 
 * Displays the token for linking in-game identity to forum account.
 *
 * @package MohaaPlayers
 * @version 1.1.0
 */

function template_mohaaidentity()
{
    global $context, $scripturl, $txt;
    
    $pendingCount = count($context['mohaa_pending_ips'] ?? []);
    $trustedCount = count($context['mohaa_trusted_ips'] ?? []);

    echo '
    <style>
        .mohaa-tabs { display: flex; border-bottom: 2px solid #ccc; margin-bottom: 0; }
        .mohaa-tab { padding: 12px 24px; cursor: pointer; border: 1px solid transparent; border-bottom: none; margin-bottom: -2px; background: #f5f5f5; border-radius: 4px 4px 0 0; margin-right: 4px; font-weight: bold; }
        .mohaa-tab:hover { background: #e8e8e8; }
        .mohaa-tab.active { background: #fff; border-color: #ccc; border-bottom-color: #fff; }
        .mohaa-tab .badge { background: #dc3545; color: white; border-radius: 50%; padding: 2px 8px; font-size: 11px; margin-left: 6px; }
        .mohaa-tab .badge-info { background: #17a2b8; }
        .mohaa-tab-content { display: none; padding: 20px; border: 1px solid #ccc; border-top: none; background: #fff; }
        .mohaa-tab-content.active { display: block; }
        .token-box { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #8bc34a; padding: 20px; border-radius: 8px; text-align: center; margin: 15px 0; }
        .token-box .token { font-family: "Courier New", monospace; font-size: 28px; letter-spacing: 4px; font-weight: bold; }
        .token-box .command { background: #000; color: #0f0; padding: 10px 15px; border-radius: 4px; margin-top: 15px; font-family: monospace; }
        .security-alert { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .security-alert h4 { color: #856404; margin: 0 0 10px 0; }
        .ip-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .ip-card.website { border-left: 4px solid #17a2b8; }
        .ip-card.game { border-left: 4px solid #28a745; }
        .ip-card.manual { border-left: 4px solid #6c757d; }
        .ip-info { flex-grow: 1; }
        .ip-address { font-family: monospace; font-size: 16px; font-weight: bold; }
        .ip-label { color: #666; font-size: 13px; margin-top: 4px; }
        .ip-meta { color: #999; font-size: 12px; margin-top: 4px; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .btn-deny { background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .btn-remove { background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-remove:hover { background: #dc3545; }
        .status-success { color: #155724; background: #d4edda; padding: 3px 10px; border-radius: 4px; font-size: 12px; }
        .status-reconnect { color: #0c5460; background: #d1ecf1; padding: 3px 10px; border-radius: 4px; font-size: 12px; }
        .status-pending { color: #856404; background: #fff3cd; padding: 3px 10px; border-radius: 4px; font-size: 12px; }
        .status-failed { color: #721c24; background: #f8d7da; padding: 3px 10px; border-radius: 4px; font-size: 12px; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state .icon { font-size: 48px; margin-bottom: 15px; }
    </style>
    
    <div class="cat_bar">
        <h3 class="catbg">
            <span class="main_icons members"></span> Game Identity & Security
        </h3>
    </div>';

    // Security Alert - Always show if there are pending IPs
    if ($pendingCount > 0) {
        echo '
    <div class="security-alert">
        <h4>⚠️ Action Required: ', $pendingCount, ' Pending IP Approval', ($pendingCount > 1 ? 's' : ''), '</h4>
        <p style="margin: 0;">Someone tried to login from a new location. Review the <strong>Pending IPs</strong> tab to approve or deny.</p>
    </div>';
    }

    echo '
    <div class="mohaa-tabs">
        <div class="mohaa-tab active" onclick="switchTab(this, \'token\')">🎫 Login Token</div>
        <div class="mohaa-tab" onclick="switchTab(this, \'pending\')">⏳ Pending IPs', ($pendingCount > 0 ? '<span class="badge">' . $pendingCount . '</span>' : ''), '</div>
        <div class="mohaa-tab" onclick="switchTab(this, \'trusted\')">🛡️ Trusted IPs<span class="badge badge-info">', $trustedCount, '</span></div>
        <div class="mohaa-tab" onclick="switchTab(this, \'history\')">📋 Login History</div>
    </div>
    
    <!-- Token Tab -->
    <div id="tab-token" class="mohaa-tab-content active">
        <h3 style="margin-top: 0;">Your Login Token</h3>
        <p>Use this token to link your in-game soldier to your forum account.</p>
        
        <div class="token-box">
            <div class="token">', $context['mohaa_token'], '</div>
            <div class="command">', $context['mohaa_console_command'], '</div>
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #aaa;">Open console with ~ and enter the command above</p>
        </div>
        
        <div style="background: #e8f5e9; border-radius: 8px; padding: 15px; margin-top: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #2e7d32;">✓ Your current IP is auto-trusted</h4>
            <p style="margin: 0; color: #666;">The IP address you are using to view this page has been automatically added to your trusted list.</p>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h4 style="color: #dc3545;">🔐 Emergency: Regenerate Token</h4>
            <p style="color: #666;">If you believe your token has been compromised, regenerate it. This will:</p>
            <ul style="color: #666;">
                <li>Create a new unique token</li>
                <li>Invalidate your old token</li>
                <li>Remove ALL trusted IPs</li>
            </ul>
            <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                <input type="hidden" name="regenerate_token" value="1">
                <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                <button type="submit" class="btn-deny" onclick="return confirm(\'Are you sure? This will invalidate your token and remove ALL trusted IPs.\');">
                    🔄 Regenerate Token
                </button>
            </form>
        </div>
    </div>
    
    <!-- Pending IPs Tab -->
    <div id="tab-pending" class="mohaa-tab-content">';
    
    if ($pendingCount > 0) {
        echo '
        <h3 style="margin-top: 0;">Pending IP Approval Requests</h3>
        <p>These IPs tried to login but are not trusted yet. Approve if it was you, deny if suspicious.</p>';
        
        foreach ($context['mohaa_pending_ips'] as $pending) {
            echo '
        <div class="ip-card" style="border-left: 4px solid #ffc107;">
            <div class="ip-info">
                <div class="ip-address">', $pending['ip_address'], '</div>
                <div class="ip-label">Server: ', htmlspecialchars($pending['server_name']), '</div>
                <div class="ip-meta">Requested: ', timeformat($pending['requested_at']), ' - Expires: ', timeformat($pending['expires_at']), '</div>
            </div>
            <div>
                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                    <input type="hidden" name="resolve_pending_ip" value="1">
                    <input type="hidden" name="approval_id" value="', $pending['id'], '">
                    <input type="hidden" name="action_type" value="approve">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="submit" class="btn-approve">✓ Approve</button>
                </form>
                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                    <input type="hidden" name="resolve_pending_ip" value="1">
                    <input type="hidden" name="approval_id" value="', $pending['id'], '">
                    <input type="hidden" name="action_type" value="deny">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="submit" class="btn-deny">✗ Deny</button>
                </form>
            </div>
        </div>';
        }
    } else {
        echo '
        <div class="empty-state">
            <div class="icon">✅</div>
            <h3>No Pending Approvals</h3>
            <p>All login attempts have been handled. You are all set!</p>
        </div>';
    }
    
    echo '
    </div>
    
    <!-- Trusted IPs Tab -->
    <div id="tab-trusted" class="mohaa-tab-content">
        <h3 style="margin-top: 0;">Trusted IP Addresses</h3>
        <p>These IPs can automatically login without needing your token each time. Remove any you do not recognize.</p>';
    
    if ($trustedCount > 0) {
        foreach ($context['mohaa_trusted_ips'] as $trusted) {
            $cardClass = 'game';
            $sourceIcon = '🎮';
            $sourceText = 'Game login';
            
            if ($trusted['source'] === 'website') {
                $cardClass = 'website';
                $sourceIcon = '🌐';
                $sourceText = 'Website';
            } elseif ($trusted['source'] === 'manual_approval') {
                $cardClass = 'manual';
                $sourceIcon = '👤';
                $sourceText = 'Manually approved';
            }
            
            echo '
        <div class="ip-card ', $cardClass, '">
            <div class="ip-info">
                <div class="ip-address">', $trusted['ip_address'], '</div>
                <div class="ip-label">', $sourceIcon, ' ', !empty($trusted['label']) ? htmlspecialchars($trusted['label']) : $sourceText, '</div>
                <div class="ip-meta">Added: ', timeformat($trusted['created_at']), ' - Last used: ', timeformat($trusted['last_used_at']), '</div>
            </div>
            <div>
                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                    <input type="hidden" name="remove_trusted_ip" value="1">
                    <input type="hidden" name="ip_id" value="', $trusted['id'], '">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="submit" class="btn-remove" onclick="return confirm(\'Remove this trusted IP?\');">Remove</button>
                </form>
            </div>
        </div>';
        }
    } else {
        echo '
        <div class="empty-state">
            <div class="icon">🔒</div>
            <h3>No Trusted IPs Yet</h3>
            <p>When you login from a game server, that IP will be automatically trusted.</p>
        </div>';
    }
    
    echo '
    </div>
    
    <!-- Login History Tab -->
    <div id="tab-history" class="mohaa-tab-content">
        <h3 style="margin-top: 0;">Recent Login Attempts</h3>
        <p>All login attempts to your account from game servers.</p>
        
        <table class="table_grid" style="width: 100%;">
            <thead>
                <tr class="title_bar">
                    <th>Date</th>
                    <th>Server</th>
                    <th>IP Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($context['mohaa_login_history'] as $login) {
        $statusClass = 'status-failed';
        if (strpos($login['status'], 'Success') !== false) {
            $statusClass = 'status-success';
        } elseif (strpos($login['status'], 'trusted_ip_reconnect') !== false) {
            $statusClass = 'status-reconnect';
        } elseif (strpos($login['status'], 'new_ip_pending') !== false) {
            $statusClass = 'status-pending';
        }
        
        $displayStatus = $login['status'];
        $displayStatus = str_replace('Failed: ', '', $displayStatus);
        $displayStatus = str_replace('_', ' ', $displayStatus);
        $displayStatus = ucfirst($displayStatus);
        
        echo '
                <tr class="windowbg">
                    <td>', timeformat($login['date']), '</td>
                    <td>', htmlspecialchars($login['server']), '</td>
                    <td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">', $login['ip'], '</code></td>
                    <td><span class="', $statusClass, '">', $displayStatus, '</span></td>
                </tr>';
    }
    
    echo '
            </tbody>
        </table>
    </div>
    
    <script>
    function switchTab(element, tabName) {
        document.querySelectorAll(".mohaa-tab-content").forEach(function(tab) {
            tab.classList.remove("active");
        });
        document.querySelectorAll(".mohaa-tab").forEach(function(tab) {
            tab.classList.remove("active");
        });
        document.getElementById("tab-" + tabName).classList.add("active");
        element.classList.add("active");
    }
    </script>';
}
?>
