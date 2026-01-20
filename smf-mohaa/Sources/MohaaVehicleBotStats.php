<?php
/**
 * MOHAA Vehicle & Bot Statistics
 * 
 * @package MOHAA Stats System
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Vehicle Statistics Page
 */
function MohaaVehicleStats()
{
	global $context, $txt, $scripturl, $modSettings;
	
	isAllowedTo('view_stats');
	loadTemplate('MohaaVehicleStats');
	
	$context['page_title'] = 'Vehicle Statistics';
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mohaa_vehicle_stats',
		'name' => 'Vehicle Stats'
	);
	
	$playerGUID = isset($_GET['guid']) ? $_GET['guid'] : null;
	$apiURL = $modSettings['mohaa_api_url'] ?? 'http://localhost:8080';
	
	if ($playerGUID) {
		$context['vehicle_stats'] = fetchPlayerVehicleStats($apiURL, $playerGUID);
		$context['sub_template'] = 'player_vehicle_stats';
	} else {
		$context['global_vehicle_stats'] = fetchGlobalVehicleStats($apiURL);
		$context['sub_template'] = 'global_vehicle_stats';
	}
}

/**
 * Bot Statistics Page
 */
function MohaaBotStats()
{
	global $context, $txt, $scripturl, $modSettings;
	
	isAllowedTo('view_stats');
	loadTemplate('MohaaBotStats');
	
	$context['page_title'] = 'Bot/AI Statistics';
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mohaa_bot_stats',
		'name' => 'Bot Stats'
	);
	
	$playerGUID = isset($_GET['guid']) ? $_GET['guid'] : null;
	$apiURL = $modSettings['mohaa_api_url'] ?? 'http://localhost:8080';
	
	if ($playerGUID) {
		$context['bot_stats'] = fetchPlayerBotStats($apiURL, $playerGUID);
		$context['sub_template'] = 'player_bot_stats';
	} else {
		$context['global_bot_stats'] = fetchGlobalBotStats($apiURL);
		$context['sub_template'] = 'global_bot_stats';
	}
}

function fetchPlayerVehicleStats($apiURL, $playerGUID)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/player/$playerGUID/vehicles");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	if (!$response) return [];
	
	$data = json_decode($response, true);
	return $data['vehicle_stats'] ?? [];
}

function fetchGlobalVehicleStats($apiURL)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/vehicles/global");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	if (!$response) return [];
	
	$data = json_decode($response, true);
	return $data['vehicles'] ?? [];
}

function fetchPlayerBotStats($apiURL, $playerGUID)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/player/$playerGUID/bots");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	if (!$response) return [];
	
	$data = json_decode($response, true);
	return $data['bot_stats'] ?? [];
}

function fetchGlobalBotStats($apiURL)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/bots/global");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	curl_close($ch);
	
	if (!$response) return [];
	
	$data = json_decode($response, true);
	return $data['bots'] ?? [];
}
