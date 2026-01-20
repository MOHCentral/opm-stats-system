<?php
/**
 * Install script for MOHAA Stats hooks
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
    require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF'))
{
    die('<b>Error:</b> Please make sure this file is included within SMF or SSI.php is available.');
}

global $smcFunc, $modSettings;

// 1. Enable settings
updateSettings(['mohaa_stats_enabled' => 1]);
echo "Enabled mohaa_stats_enabled setting.<br>";

// 2. Define hooks
$hooks = [
    'integrate_actions' => [
        'MohaaPlayers.php|MohaaPlayers_Actions',
        'MohaaServers.php|MohaaServers_Actions',
        'MohaaAchievements.php|MohaaAchievements_Actions',
        'MohaaTournaments.php|MohaaTournaments_Actions',
        'MohaaTeams.php|MohaaTeams_Actions',
    ],
    'integrate_menu_buttons' => 'MohaaPlayers.php|MohaaPlayers_MenuButtons',
    'integrate_profile_areas' => [
        'MohaaPlayers.php|MohaaPlayers_ProfileAreas',
        'MohaaAchievements.php|MohaaAchievements_ProfileAreas',
        'MohaaTeams.php|MohaaTeams_ProfileAreas',
    ],
    'integrate_admin_areas' => 'MohaaTournaments.php|MohaaTournaments_AdminAreas',
];

// 3. Register hooks
foreach ($hooks as $hook => $functions) {
    if (!is_array($functions)) {
        $functions = [$functions];
    }
    
    foreach ($functions as $function) {
        // SMF 2.1+ add_integration_function handles duplication checks
        add_integration_function($hook, $function, true);
        echo "Registered hook $hook -> $function<br>";
    }
}

echo "MOHAA Stats installation complete!";
?>
