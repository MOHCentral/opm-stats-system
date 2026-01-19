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
        <div style="padding: 20px; max-width: 800px; margin: 0 auto;">
            
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
            </div>

            <div class="cat_bar">
                <h3 class="catbg">Recent Logins</h3>
            </div>
            <table class="table_grid">
                <thead>
                    <tr class="title_bar">
                        <th scope="col">Date</th>
                        <th scope="col">Server</th>
                        <th scope="col">IP Address</th>
                        <th scope="col">Location</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($context['mohaa_login_history'] as $login) {
        $statusStyle = strpos($login['status'], 'Success') !== false ? 'color: green;' : 'color: red;';
        echo '
                    <tr class="windowbg">
                        <td>', timeformat($login['date']), '</td>
                        <td>', $login['server'], '</td>
                        <td>', $login['ip'], '</td>
                        <td>', $login['location'], '</td>
                        <td style="', $statusStyle, ' font-weight: bold;">', $login['status'], '</td>
                    </tr>';
    }

    echo '
                </tbody>
            </table>

            <div style="text-align: center; margin-top: 30px;">
                <form action="', $scripturl, '?action=profile;area=mohaaidentity" method="post" style="display: inline;">
                    <input type="hidden" name="regenerate_token" value="1">
                    <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
                    <button type="submit" class="button" onclick="return confirm(\'Are you sure you want to regenerate your token? Your old token will no longer work.\');">
                        Generate New Token
                    </button>
                </form>
                <a href="', $scripturl, '?action=mohaa" class="button">Back to War Room</a>
                <br><br>
                <div class="smalltext">If you see unrecognized logins, please regenerate your token immediately.</div>
            </div>

        </div>
    </div>';
}
?>
