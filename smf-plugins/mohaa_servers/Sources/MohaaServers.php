<?php
/**
 * MOHAA Server Stats Plugin
 * 
 * Server browser, live server status, server statistics
 *
 * @package MohaaServers
 * @version 1.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Register actions
 */
function MohaaServers_Actions(array &$actions): void
{
    $actions['mohaaservers'] = ['MohaaServers.php', 'MohaaServers_Main'];
}

/**
 * Main server stats dispatcher
 */
function MohaaServers_Main(): void
{
    global $context, $txt, $modSettings;
    
    if (empty($modSettings['mohaa_stats_enabled'])) {
        fatal_error($txt['mohaa_stats_disabled'], false);
        return;
    }
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaServers');
    
    $subActions = [
        'list' => 'MohaaServers_List',
        'live' => 'MohaaServers_Live',
        'server' => 'MohaaServers_Detail',
        'history' => 'MohaaServers_History',
    ];
    
    $sa = isset($_GET['sa']) && isset($subActions[$_GET['sa']]) ? $_GET['sa'] : 'list';
    
    call_user_func($subActions[$sa]);
}

/**
 * Server list page - all registered servers
 */
function MohaaServers_List(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_servers'];
    $context['sub_template'] = 'mohaa_servers_list';
    
    require_once(SOURCEDIR . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_servers'] = $api->getServerList();
    $context['mohaa_server_stats'] = $api->getServerGlobalStats();
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'],
    ];
}

/**
 * Live servers page - currently active
 */
function MohaaServers_Live(): void
{
    global $context, $txt, $scripturl;
    
    $context['page_title'] = $txt['mohaa_live_servers'];
    $context['sub_template'] = 'mohaa_servers_live';
    
    require_once(SOURCEDIR . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $context['mohaa_live'] = $api->getLiveServers();
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=live',
        'name' => $txt['mohaa_live_servers'],
    ];
}

/**
 * Server detail page
 */
function MohaaServers_Detail(): void
{
    global $context, $txt, $scripturl;
    
    $serverId = isset($_GET['id']) ? $_GET['id'] : '';
    
    if (empty($serverId)) {
        redirectexit('action=mohaaservers');
        return;
    }
    
    require_once(SOURCEDIR . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    $server = $api->getServerDetails($serverId);
    
    if (empty($server)) {
        fatal_lang_error('mohaa_server_not_found', false);
        return;
    }
    
    $context['page_title'] = $server['name'];
    $context['sub_template'] = 'mohaa_server_detail';
    
    $context['mohaa_server'] = [
        'info' => $server,
        'current_match' => $api->getServerCurrentMatch($serverId),
        'recent_matches' => $api->getServerMatches($serverId, 20),
        'top_players' => $api->getServerTopPlayers($serverId, 10),
        'map_rotation' => $api->getServerMapRotation($serverId),
        'uptime_history' => $api->getServerUptimeHistory($serverId, 7), // Last 7 days
        'player_history' => $api->getServerPlayerHistory($serverId, 24), // Last 24 hours
    ];
    
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers',
        'name' => $txt['mohaa_servers'],
    ];
    $context['linktree'][] = [
        'url' => $scripturl . '?action=mohaaservers;sa=server;id=' . urlencode($serverId),
        'name' => $server['name'],
    ];
}

/**
 * Server history page - past activity
 */
function MohaaServers_History(): void
{
    global $context, $txt, $scripturl;
    
    $serverId = isset($_GET['id']) ? $_GET['id'] : '';
    $period = isset($_GET['period']) ? $_GET['period'] : 'week';
    
    $context['page_title'] = $txt['mohaa_server_history'];
    $context['sub_template'] = 'mohaa_server_history';
    
    require_once(SOURCEDIR . '/MohaaStats/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    if (!empty($serverId)) {
        $server = $api->getServerDetails($serverId);
        $context['mohaa_server'] = $server;
    }
    
    $days = match($period) {
        'day' => 1,
        'week' => 7,
        'month' => 30,
        'year' => 365,
        default => 7,
    };
    
    $context['mohaa_history'] = [
        'period' => $period,
        'servers' => empty($serverId) ? $api->getAllServersHistory($days) : null,
        'single' => !empty($serverId) ? $api->getServerHistory($serverId, $days) : null,
        'player_counts' => $api->getHistoricalPlayerCounts($serverId, $days),
        'match_counts' => $api->getHistoricalMatchCounts($serverId, $days),
    ];
}
