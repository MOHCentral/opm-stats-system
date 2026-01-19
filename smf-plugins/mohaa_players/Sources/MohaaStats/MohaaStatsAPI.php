<?php
if (!defined("SMF")) die("No direct access...");

class MohaaStatsAPIClient
{
    private string $baseUrl;
    private string $serverToken;
    private int $cacheDuration;
    private int $timeout;
    
    public function __construct()
    {
        global $modSettings;
        $this->baseUrl = $modSettings["mohaa_stats_api_url"] ?? "http://api:8080";
        $this->serverToken = $modSettings["mohaa_stats_server_token"] ?? "";
        $this->cacheDuration = (int)($modSettings["mohaa_stats_cache_duration"] ?? 60);
        $this->timeout = (int)($modSettings["mohaa_stats_api_timeout"] ?? 3);
    }
    
    public function getMultiple(array $requests): array
    {
        $results = [];
        $handles = [];
        $mh = curl_multi_init();
        
        foreach ($requests as $key => $request) {
            $endpoint = $request["endpoint"];
            $params = $request["params"] ?? [];
            $url = $this->baseUrl . "/api/v1" . $endpoint;
            if (!empty($params)) $url .= "?" . http_build_query($params);
            
            $cacheKey = "mohaa_api_" . md5($url);
            $cached = cache_get_data($cacheKey, $this->cacheDuration);
            if ($cached !== null) { $results[$key] = $cached; continue; }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER => ["Accept: application/json", "X-Server-Token: " . $this->serverToken],
            ]);
            $handles[$key] = ["handle" => $ch, "url" => $url, "cacheKey" => $cacheKey];
            curl_multi_add_handle($mh, $ch);
        }
        
        if (!empty($handles)) {
            $running = null;
            do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
            
            foreach ($handles as $key => $info) {
                $response = curl_multi_getcontent($info["handle"]);
                $httpCode = curl_getinfo($info["handle"], CURLINFO_HTTP_CODE);
                if ($httpCode === 200 && $response !== false) {
                    $data = json_decode($response, true);
                    $results[$key] = $data;
                    cache_put_data($info["cacheKey"], $data, $this->cacheDuration);
                } else { $results[$key] = null; }
                curl_multi_remove_handle($mh, $info["handle"]);
                curl_close($info["handle"]);
            }
            curl_multi_close($mh);
        }
        return $results;
    }
    
    private function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . "/api/v1" . $endpoint;
        if (!empty($params)) $url .= "?" . http_build_query($params);
        $cacheKey = "mohaa_api_" . md5($url);
        $cached = cache_get_data($cacheKey, $this->cacheDuration);
        if ($cached !== null) return $cached;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_HTTPHEADER => ["Accept: application/json", "X-Server-Token: " . $this->serverToken],
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
        $url = $this->baseUrl . "/api/v1" . $endpoint;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ["Accept: application/json", "Content-Type: application/json", "X-Server-Token: " . $this->serverToken],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode < 200 || $httpCode >= 300 || $response === false) return null;
        return json_decode($response, true);
    }
    
    public function clearCache(): void { clean_cache("mohaa_api_"); }
    public function getGlobalStats(): ?array { return $this->get("/stats/global"); }
    public function getLeaderboard(string $stat = "kills", int $limit = 25, int $offset = 0, string $period = "all"): ?array { return $this->get("/stats/leaderboard/global", ["stat"=>$stat,"limit"=>$limit,"offset"=>$offset,"period"=>$period]); }
    public function getLeaderboardCount(string $stat = "kills", string $period = "all"): int { $data = $this->get("/stats/leaderboard/global", ["stat"=>$stat,"period"=>$period,"count_only"=>true]); return $data["total"] ?? 0; }
    public function getPlayerStats(string $guid): ?array { return $this->get("/stats/player/" . urlencode($guid)); }
    public function getPlayerWeapons(string $guid): ?array { return $this->get("/stats/player/" . urlencode($guid) . "/weapons"); }
    public function getPlayerMatches(string $guid, int $limit = 10, int $offset = 0): ?array { return $this->get("/stats/player/" . urlencode($guid) . "/matches", ["limit"=>$limit,"offset"=>$offset]); }
    public function getPlayerAchievements(string $guid): ?array { return $this->get("/achievements/player/" . urlencode($guid)); }
    public function getRecentMatches(int $limit = 20, int $offset = 0): ?array { return $this->get("/stats/matches", ["limit"=>$limit,"offset"=>$offset]); }
    public function getMatchCount(): int { $data = $this->get("/stats/matches", ["count_only"=>true]); return $data["total"] ?? 0; }
    public function getMatchDetails(string $matchId): ?array { return $this->get("/stats/match/" . urlencode($matchId)); }
    public function getLiveMatches(): ?array { $orig = $this->cacheDuration; $this->cacheDuration = 10; $data = $this->get("/stats/live/matches"); $this->cacheDuration = $orig; return $data; }
    public function getMapStats(): ?array { return $this->get("/stats/maps"); }
    public function getMapDetails(string $mapId): ?array { return $this->get("/stats/map/" . urlencode($mapId)); }
    public function getMapHeatmap(string $mapId, string $type = "kills"): ?array { return $this->get("/stats/map/" . urlencode($mapId) . "/heatmap", ["type"=>$type]); }
    public function getMapsList(): ?array { return $this->get("/stats/maps/list"); }
    public function getWeaponsList(): ?array { return $this->get("/stats/weapons/list"); }
    public function getWeaponStats(string $weaponId): ?array { return $this->get("/stats/weapon/" . urlencode($weaponId)); }
    public function initClaim(int $forumUserId): ?array { return $this->post("/auth/claim/init", ["forum_user_id"=>$forumUserId]); }
    
    /**
     * Initialize device authentication - generate a login token
     * @param int $forumUserId The SMF member ID
     * @param bool $regenerate Force generation of a new token (revokes old ones)
     * @return array|null Response with user_code, expires_in, etc.
     */
    public function initDeviceAuth(int $forumUserId, bool $regenerate = false): ?array { 
        return $this->post("/auth/device", [
            "forum_user_id" => $forumUserId,
            "regenerate" => $regenerate
        ]); 
    }
    
    /**
     * Get login history for a forum user
     * @param int $forumUserId The SMF member ID
     * @return array|null Response with history array
     */
    public function getLoginHistory(int $forumUserId): ?array {
        return $this->get("/auth/history", ["forum_user_id" => $forumUserId]);
    }
    
    public function getGlobalWeaponStats(): ?array { return []; }
    public function getActivePlayers(int $hours): ?array { return []; }
    public function getPlayerRank(string $guid): ?int { return null; }
    public function getPlayerPerformance(string $guid, int $days): ?array { return []; }
    public function getPlayerMapStats(string $guid): ?array { return []; }
    public function getPlayerComparisons(string $guid): ?array { return []; }
    public function getHeadToHead(string $guid1, string $guid2): ?array { return []; }
}

function MohaaStats_APIProxy(): void
{
    global $modSettings;
    if (empty($modSettings["mohaa_stats_enabled"])) { http_response_code(503); die(json_encode(["error"=>"disabled"])); }
    header("Content-Type: application/json");
    $endpoint = $_GET["endpoint"] ?? "";
    $api = new MohaaStatsAPIClient();
    $result = null;
    switch($endpoint) {
        case "global-stats": $result = $api->getGlobalStats(); break;
        case "leaderboard": $result = $api->getLeaderboard($_GET["stat"]??"kills", min(100,max(1,(int)($_GET["limit"]??25))), max(0,(int)($_GET["offset"]??0)), $_GET["period"]??"all"); break;
        case "player": $result = $api->getPlayerStats($_GET["guid"]??""); break;
        case "matches": $result = $api->getRecentMatches(min(50,max(1,(int)($_GET["limit"]??20))), max(0,(int)($_GET["offset"]??0))); break;
        case "match": $result = $api->getMatchDetails($_GET["id"]??""); break;
        case "maps": $result = $api->getMapStats(); break;
        case "live": $result = $api->getLiveMatches(); break;
    }
    if ($result === null) { http_response_code(500); die(json_encode(["error"=>"API failed"])); }
    echo json_encode($result);
    obExit(false);
}
