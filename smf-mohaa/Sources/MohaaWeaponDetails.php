<?php
/**
 * MOHAA Weapon Details
 * 
 * Comprehensive weapon statistics page including:
 * - Reload efficiency metrics
 * - Ammo management analysis  
 * - Reload time distribution
 * - Reload frequency charts
 * - Weapon-specific performance breakdown
 * 
 * @package MOHAA Stats System
 */

if (!defined('SMF'))
	die('No direct access...');

/**
 * Main weapon details page
 */
function MohaaWeaponDetails()
{
	global $context, $txt, $scripturl, $modSettings;
	
	isAllowedTo('view_stats');
	loadTemplate('MohaaWeaponDetails');
	
	$context['page_title'] = 'Weapon Statistics';
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mohaa_weapon_details',
		'name' => 'Weapon Details'
	);
	
	// Get player ID from URL
	$playerGUID = isset($_GET['guid']) ? $_GET['guid'] : null;
	$weapon = isset($_GET['weapon']) ? $_GET['weapon'] : null;
	
	$apiURL = $modSettings['mohaa_api_url'] ?? 'http://localhost:8080';
	
	if ($playerGUID && $weapon) {
		// Specific weapon details for a player
		$context['weapon_stats'] = fetchWeaponStats($apiURL, $playerGUID, $weapon);
		$context['sub_template'] = 'weapon_detail';
	} elseif ($playerGUID) {
		// All weapons for a player
		$context['weapon_list'] = fetchPlayerWeapons($apiURL, $playerGUID);
		$context['sub_template'] = 'player_weapons';
	} else {
		// Global weapon leaderboards
		$context['weapon_leaderboard'] = fetchGlobalWeaponStats($apiURL);
		$context['sub_template'] = 'weapon_leaderboard';
	}
}

/**
 * Fetch comprehensive weapon stats for a specific weapon
 */
function fetchWeaponStats($apiURL, $playerGUID, $weapon)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/player/$playerGUID/weapon/" . urlencode($weapon));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpCode !== 200 || !$response) {
		return null;
	}
	
	$data = json_decode($response, true);
	if (!$data) {
		return null;
	}
	
	// Calculate derived metrics
	$stats = $data['weapon_stats'] ?? [];
	
	// Reload Efficiency = Successful Reloads / Total Reloads
	$totalReloads = ($stats['reload_count'] ?? 0);
	$successfulReloads = ($stats['reload_done_count'] ?? 0);
	$stats['reload_efficiency'] = $totalReloads > 0 
		? round(($successfulReloads / $totalReloads) * 100, 2) 
		: 0;
	
	// Ammo Waste = Ammo Remaining When Reloading
	$stats['avg_ammo_wasted'] = ($stats['total_ammo_wasted'] ?? 0) / max($totalReloads, 1);
	
	// Shots Per Reload Cycle
	$stats['shots_per_reload'] = ($stats['shots_fired'] ?? 0) / max($totalReloads, 1);
	
	// Reload Time Analysis
	$stats['avg_reload_time'] = ($stats['total_reload_time'] ?? 0) / max($successfulReloads, 1);
	$stats['fastest_reload'] = $stats['min_reload_time'] ?? 0;
	$stats['slowest_reload'] = $stats['max_reload_time'] ?? 0;
	
	// Tactical Reload % (reloading with >50% ammo remaining)
	$tacticalReloads = $stats['tactical_reload_count'] ?? 0;
	$stats['tactical_reload_pct'] = $totalReloads > 0 
		? round(($tacticalReloads / $totalReloads) * 100, 2) 
		: 0;
	
	// Combat Performance
	$stats['accuracy'] = ($stats['shots_fired'] ?? 0) > 0 
		? round((($stats['shots_hit'] ?? 0) / $stats['shots_fired']) * 100, 2) 
		: 0;
	
	$stats['headshot_pct'] = ($stats['kills'] ?? 0) > 0 
		? round((($stats['headshots'] ?? 0) / $stats['kills']) * 100, 2) 
		: 0;
	
	$stats['kd_ratio'] = ($stats['deaths_with_weapon'] ?? 0) > 0 
		? round(($stats['kills'] ?? 0) / $stats['deaths_with_weapon'], 2) 
		: ($stats['kills'] ?? 0);
	
	return $stats;
}

/**
 * Fetch all weapons used by a player
 */
function fetchPlayerWeapons($apiURL, $playerGUID)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/player/$playerGUID/weapons");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpCode !== 200 || !$response) {
		return [];
	}
	
	$data = json_decode($response, true);
	return $data['weapons'] ?? [];
}

/**
 * Fetch global weapon leaderboards
 */
function fetchGlobalWeaponStats($apiURL)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "$apiURL/stats/weapons/global");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpCode !== 200 || !$response) {
		return [];
	}
	
	$data = json_decode($response, true);
	
	// Group by weapon type
	$weapons = $data['weapons'] ?? [];
	$grouped = [
		'rifles' => [],
		'submachine_guns' => [],
		'heavy' => [],
		'pistols' => [],
		'grenades' => [],
	];
	
	$riflePattern = '/(Kar98|Springfield|M1 Garand)/i';
	$smgPattern = '/(Thompson|MP40|StG|Sten)/i';
	$heavyPattern = '/(BAR|MG42|Bazooka|Panzerschreck)/i';
	$pistolPattern = '/(Colt|Walther|Webley)/i';
	$grenadePattern = '/(Grenade|Potato)/i';
	
	foreach ($weapons as $weapon) {
		$name = $weapon['weapon_name'];
		
		if (preg_match($riflePattern, $name)) {
			$grouped['rifles'][] = $weapon;
		} elseif (preg_match($smgPattern, $name)) {
			$grouped['submachine_guns'][] = $weapon;
		} elseif (preg_match($heavyPattern, $name)) {
			$grouped['heavy'][] = $weapon;
		} elseif (preg_match($pistolPattern, $name)) {
			$grouped['pistols'][] = $weapon;
		} elseif (preg_match($grenadePattern, $name)) {
			$grouped['grenades'][] = $weapon;
		}
	}
	
	return $grouped;
}
