<?php
/**
 * MOHAA Stats SMF Integration
 * 
 * This file provides the API connection layer for SMF plugins
 * to communicate with the MOHAA Stats API.
 */

// API Configuration
$mohaa_config = [
    'api_url' => getenv('MOHAA_API_URL') ?: 'http://172.17.0.1:8080',
    'api_timeout' => 30,
    'cache_ttl' => 300,  // 5 minutes
];

/**
 * Make an API request to the MOHAA Stats API
 */
function mohaa_api_request($endpoint, $method = 'GET', $data = null) {
    global $mohaa_config;
    
    $url = rtrim($mohaa_config['api_url'], '/') . '/' . ltrim($endpoint, '/');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $mohaa_config['api_timeout']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'code' => 0];
    }
    
    $decoded = json_decode($response, true);
    return [
        'data' => $decoded,
        'code' => $http_code,
        'success' => $http_code >= 200 && $http_code < 300,
    ];
}

/**
 * Get player statistics
 */
function mohaa_get_player_stats($player_id) {
    return mohaa_api_request("/api/v1/players/{$player_id}/stats");
}

/**
 * Get leaderboard
 */
function mohaa_get_leaderboard($limit = 10, $stat = 'kills') {
    return mohaa_api_request("/api/v1/leaderboard?limit={$limit}&stat={$stat}");
}

/**
 * Get server list
 */
function mohaa_get_servers() {
    return mohaa_api_request("/api/v1/servers");
}

/**
 * Get player achievements
 */
function mohaa_get_achievements($player_id) {
    return mohaa_api_request("/api/v1/players/{$player_id}/achievements");
}

/**
 * Get API health status
 */
function mohaa_health_check() {
    return mohaa_api_request("/health");
}

/**
 * Search players
 */
function mohaa_search_players($query, $limit = 20) {
    $query = urlencode($query);
    return mohaa_api_request("/api/v1/players/search?q={$query}&limit={$limit}");
}
