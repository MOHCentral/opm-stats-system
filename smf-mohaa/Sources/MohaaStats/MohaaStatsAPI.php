<?php
/**
 * MOHAA Stats API Client - OPTIMIZED with parallel requests
 */

if (!defined('SMF'))
    die('No direct access...');

class MohaaStatsAPIClient
{
    private string $baseUrl;
    private string $serverToken;
    private int $cacheDuration;
    private int $timeout;
    
    public function __construct()
    {
        global $modSettings;
        // Default to localhost for local dev (Apache), use modSettings override for Docker
        $this->baseUrl = $modSettings['mohaa_stats_api_url'] ?? 'http://localhost:8080';
        $this->serverToken = $modSettings['mohaa_stats_server_token'] ?? '';
        $this->cacheDuration = (int)($modSettings['mohaa_stats_cache_duration'] ?? 60);
        $this->timeout = (int)($modSettings['mohaa_stats_api_timeout'] ?? 3);
    }
    
    public function getMultiple(array $requests): array
    {
        $results = [];
        $handles = [];
        $mh = curl_multi_init();
        
        foreach ($requests as $key => $request) {
            $endpoint = $request['endpoint'];
            $params = $request['params'] ?? [];
            $url = $this->baseUrl . '/api/v1' . $endpoint;
            if (!empty($params)) $url .= '?' . http_build_query($params);
            
            $cacheKey = 'mohaa_api_' . md5($url);
            $cached = cache_get_data($cacheKey, $this->cacheDuration);
            if ($cached !== null) { $results[$key] = $cached; continue; }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Server-Token: ' . $this->serverToken],
            ]);
            $handles[$key] = ['handle' => $ch, 'url' => $url, 'cacheKey' => $cacheKey];
            curl_multi_add_handle($mh, $ch);
        }
        
        if (!empty($handles)) {
            $running = null;
            do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
            
            foreach ($handles as $key => $info) {
                $response = curl_multi_getcontent($info['handle']);
                $httpCode = curl_getinfo($info['handle'], CURLINFO_HTTP_CODE);
                if ($httpCode === 200 && $response !== false) {
                    $data = json_decode($response, true);
                    $results[$key] = $data;
                    cache_put_data($info['cacheKey'], $data, $this->cacheDuration);
                } else { $results[$key] = null; }
                curl_multi_remove_handle($mh, $info['handle']);
                curl_close($info['handle']);
            }
            curl_multi_close($mh);
        }
        return $results;
    }
    
    private function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . '/api/v1' . $endpoint;
        if (!empty($params)) $url .= '?' . http_build_query($params);
        $cacheKey = 'mohaa_api_' . md5($url);
        $cached = cache_get_data($cacheKey, $this->cacheDuration);
        if ($cached !== null) return $cached;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Server-Token: ' . $this->serverToken],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || $response === false) return null;
        $data = json_decode($response, true);
        cache_put_data($cacheKey, $data, $this->cacheDuration);
        return $data;
    }
    
    private function post(string $endpoint, array $data = []): ?array
    {
        $url = $this->baseUrl . '/api/v1' . $endpoint;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'X-Server-Token: ' . $this->serverToken],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode < 200 || $httpCode >= 300 || $response === false) return null;
        return json_decode($response, true);
    }
    
    public function clearCache(): void { clean_cache('mohaa_api_'); }
    public function getGlobalStats(): ?array { return $this->get('/stats/global'); }
    public function getLeaderboard(string $stat = 'kills', int $limit = 25, int $offset = 0, string $period = 'all'): ?array { return $this->get('/stats/leaderboard/global', ['stat'=>$stat,'limit'=>$limit,'offset'=>$offset,'period'=>$period]); }
    public function getLeaderboardCount(string $stat = 'kills', string $period = 'all'): int { $data = $this->get('/stats/leaderboard/global', ['stat'=>$stat,'period'=>$period,'count_only'=>true]); return $data['total'] ?? 0; }
    public function getPlayerStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid)); }
    public function getPlayerDeepStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/deep'); }
    public function getPlayerWeapons(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/weapons'); }
    public function getPlayerMatches(string $guid, int $limit = 10, int $offset = 0): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/matches', ['limit'=>$limit,'offset'=>$offset]); }
    public function getPlayerAchievements(string $guid): ?array { return $this->get('/achievements/player/' . urlencode($guid)); }
    public function getRecentMatches(int $limit = 20, int $offset = 0): ?array { return $this->get('/stats/matches', ['limit'=>$limit,'offset'=>$offset]); }
    public function getMatchCount(): int { $data = $this->get('/stats/matches', ['count_only'=>true]); return $data['total'] ?? 0; }
    public function getMatchDetails(string $matchId): ?array { return $this->get('/stats/match/' . urlencode($matchId)); }
    public function getLiveMatches(): ?array { $orig = $this->cacheDuration; $this->cacheDuration = 10; $data = $this->get('/stats/live/matches'); $this->cacheDuration = $orig; return $data; }
    public function getMapStats(): ?array { return $this->get('/stats/maps'); }
    public function getMapDetails(string $mapId): ?array { return $this->get('/stats/map/' . urlencode($mapId)); }
    public function getMapHeatmap(string $mapId, string $type = 'kills'): ?array { return $this->get('/stats/map/' . urlencode($mapId) . '/heatmap', ['type'=>$type]); }
    public function getMapsList(): ?array { return $this->get('/stats/maps/list'); }
    public function getWeaponsList(): ?array { return $this->get('/stats/weapons/list'); }
    public function getWeaponStats(string $weaponId): ?array { return $this->get('/stats/weapon/' . urlencode($weaponId)); }
    public function initClaim(int $forumUserId): ?array { return $this->post('/auth/claim/init', ['forum_user_id'=>$forumUserId]); }
    public function initDeviceAuth(int $forumUserId, bool $regenerate = false): ?array { return $this->post('/auth/device', ['forum_user_id'=>$forumUserId, 'regenerate'=>$regenerate]); }

    // Server Stats
    public function getGlobalActivity(): ?array { return $this->get('/stats/global/activity'); }
    public function getMapPopularity(): ?array { return $this->get('/stats/maps/popularity'); }
    
    // Stubs
    public function getAchievements(): ?array { return $this->get('/achievements/'); }
    public function getAchievement(int $id): ?array { return $this->get('/achievements/' . $id); }
    public function getRecentAchievements(): ?array { return $this->get('/achievements/recent'); }
    
    // Server methods - stub implementations until Go API has server endpoints
    public function getServerList(): array { return []; }
    public function getServerGlobalStats(): array { return ['total_servers' => 0, 'online_servers' => 0, 'total_players' => 0]; }
    public function getLiveServers(): array { return []; }
    public function getServerDetails(string $id): ?array { return null; }
    public function getServerCurrentMatch(string $id): ?array { return null; }
    public function getServerMatches(string $id, int $limit = 20): array { return []; }
    public function getServerTopPlayers(string $id, int $limit = 10): array { return []; }
    public function getServerMapRotation(string $id): array { return []; }
    public function getServerUptimeHistory(string $id, int $days = 7): array { return []; }
    public function getServerPlayerHistory(string $id, int $hours = 24): array { return []; }
    public function getAllServersHistory(int $days = 7): array { return []; }
    public function getServerHistory(string $id, int $days = 7): array { return []; }
    public function getHistoricalPlayerCounts(string $id, int $days = 7): array { return []; }
    public function getHistoricalMatchCounts(string $id, int $days = 7): array { return []; }
    public function getAchievementLeaderboard(): ?array { return $this->get('/achievements/leaderboard'); }

    public function getActivePlayers(int $hours): ?array { return []; }
    public function getPlayerRank(string $guid): ?int { return null; }
    public function getPlayerPerformance(string $guid, int $days): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/performance'); }
    public function getPlayerHistory(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/performance'); }
    public function getPlayerPlaystyle(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/playstyle'); }
    public function getMatchReport(string $matchId): ?array { return $this->get('/stats/match/' . urlencode($matchId) . '/advanced'); }

    // War Room Enhanced endpoints
    public function getPlayerPeakPerformance(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/peak-performance'); }
    public function getPlayerComboMetrics(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/combos'); }
    public function getPlayerDrilldown(string $guid, string $stat = 'kills', string $dimension = 'weapon', int $limit = 10): ?array { 
        return $this->get('/stats/player/' . urlencode($guid) . '/drilldown', [
            'stat' => $stat,
            'dimension' => $dimension,
            'limit' => $limit
        ]); 
    }
    public function getPlayerVehicleStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/vehicles'); }
    public function getPlayerGameFlowStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/game-flow'); }
    public function getPlayerWorldStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/world'); }
    public function getPlayerBotStats(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/bots'); }
    public function getPlayerWarRoomData(string $guid): ?array { return $this->get('/stats/player/' . urlencode($guid) . '/war-room'); }
    
    // Enhanced Leaderboards
    public function getContextualLeaderboard(string $stat, string $dimension, string $value, int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/contextual', [
            'stat' => $stat,
            'dimension' => $dimension,
            'value' => $value,
            'limit' => $limit
        ]);
    }
    public function getComboLeaderboard(string $metric, int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/combos', ['metric' => $metric, 'limit' => $limit]);
    }
    public function getPeakPerformanceLeaderboard(string $dimension = 'evening', int $limit = 25): ?array {
        return $this->get('/stats/leaderboard/peak', ['dimension' => $dimension, 'limit' => $limit]);
    }
    public function getDrilldownOptions(string $stat = 'kd'): ?array { return $this->get('/stats/drilldown/options', ['stat' => $stat]); }

    public function getPlayerMapStats(string $guid): ?array { return []; }
    public function getPlayerComparisons(string $guid): ?array { return []; }
    public function getHeadToHead(string $guid1, string $guid2): ?array { return []; }
    public function getLeaderboardCards(): ?array { return $this->get('/stats/leaderboard/cards'); }

    // Auth/Identity methods
    public function getLoginHistory(int $memberId): ?array { return $this->get('/auth/login-history/' . $memberId); }
    public function getTrustedIPs(int $memberId): ?array { return $this->get('/auth/trusted-ips/' . $memberId); }
    public function getPendingIPApprovals(int $memberId): ?array { return $this->get('/auth/pending-approvals/' . $memberId); }
    public function deleteTrustedIP(int $memberId, int $ipId): ?array { return $this->post('/auth/trusted-ips/' . $memberId . '/delete', ['ip_id' => $ipId]); }
    public function resolvePendingIP(int $memberId, int $approvalId, string $action): ?array { return $this->post('/auth/pending-approvals/' . $memberId . '/resolve', ['approval_id' => $approvalId, 'action' => $action]); }
}

function MohaaStats_APIProxy(): void
{
    global $modSettings;
    if (empty($modSettings['mohaa_stats_enabled'])) { http_response_code(503); die(json_encode(['error'=>'disabled'])); }
    header('Content-Type: application/json');
    $endpoint = $_GET['endpoint'] ?? '';
    $api = new MohaaStatsAPIClient();
    $result = match($endpoint) {
        'global-stats' => $api->getGlobalStats(),
        'leaderboard' => $api->getLeaderboard($_GET['stat']??'kills', min(100,max(1,(int)($_GET['limit']??25))), max(0,(int)($_GET['offset']??0)), $_GET['period']??'all'),
        'player' => $api->getPlayerStats($_GET['guid']??''),
        'matches' => $api->getRecentMatches(min(50,max(1,(int)($_GET['limit']??20))), max(0,(int)($_GET['offset']??0))),
        'match' => $api->getMatchDetails($_GET['id']??''),
        'maps' => $api->getMapStats(),
        'live' => $api->getLiveMatches(),
        default => null,
    };
    if ($result === null) { http_response_code(500); die(json_encode(['error'=>'API failed'])); }
    echo json_encode($result);
    obExit(false);
}
