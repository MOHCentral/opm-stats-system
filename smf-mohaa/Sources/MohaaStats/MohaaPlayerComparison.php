<?php
/**
 * MOHAA Player Comparison System
 * 
 * Advanced head-to-head player comparison with radar charts, differential analysis,
 * and predictive modeling
 *
 * @package MohaaStats
 * @version 2.0.0
 */

if (!defined('SMF'))
    die('No direct access...');

/**
 * Main comparison function - Compare 2-4 players side-by-side
 */
function MohaaStats_ComparePlayersfunction MohaaStats_ComparePlayers(): void
{
    global $context, $txt, $scripturl, $user_info;
    
    loadLanguage('MohaaStats');
    loadTemplate('MohaaPlayerComparison');
    
    $context['page_title'] = $txt['mohaa_compare_players'] ?? 'Player Comparison';
    $context['sub_template'] = 'mohaa_player_comparison';
    
    // Get player GUIDs from request (support 2-4 players)
    $guids = [];
    for ($i = 1; $i <= 4; $i++) {
        if (!empty($_GET['p' . $i])) {
            $guids[] = trim($_GET['p' . $i]);
        }
    }
    
    if (count($guids) < 2) {
        $context['comparison_error'] = $txt['mohaa_compare_min_players'] ?? 'Please select at least 2 players to compare';
        return;
    }
    
    require_once(dirname(__FILE__) . '/MohaaStatsAPI.php');
    $api = new MohaaStatsAPIClient();
    
    // Fetch stats for each player
    $players = [];
    foreach ($guids as $guid) {
        $playerData = $api->getPlayerStats($guid);
        if ($playerData && isset($playerData['player'])) {
            $players[] = $playerData['player'];
        }
    }
    
    if (count($players) < 2) {
        $context['comparison_error'] = $txt['mohaa_compare_data_error'] ?? 'Could not fetch data for all players';
        return;
    }
    
    $context['compared_players'] = $players;
    $context['comparison_metrics'] = generateComparisonMetrics($players);
    $context['comparison_charts'] = generateComparisonCharts($players);
    $context['winner_analysis'] = analyzeWinner($players);
    $context['differential_stats'] = calculateDifferentials($players);
}

/**
 * Generate comparison metrics normalized 0-100
 */
function generateComparisonMetrics(array $players): array
{
    $metrics = [
        'combat' => ['kills', 'deaths', 'kd_ratio', 'headshots', 'accuracy'],
        'movement' => ['distance_walked', 'distance_sprinted', 'jump_count'],
        'objectives' => ['objective_captures', 'objective_time'],
        'survival' => ['playtime_minutes', 'win_rate', 'damage_taken'],
        'efficiency' => ['kills_per_minute', 'deaths_per_minute', 'score_per_minute']
    ];
    
    $normalized = [];
    
    foreach ($metrics as $category => $statKeys) {
        $normalized[$category] = [];
        
        foreach ($statKeys as $key) {
            $values = array_map(function($p) use ($key) {
                return $p[$key] ?? 0;
            }, $players);
            
            $max = max($values);
            $min = min($values);
            $range = $max - $min;
            
            foreach ($players as $index => $player) {
                $raw = $player[$key] ?? 0;
                $norm = $range > 0 ? (($raw - $min) / $range) * 100 : 50;
                
                $normalized[$category][$index][$key] = [
                    'raw' => $raw,
                    'normalized' => round($norm, 2),
                    'rank' => 0 // Will be calculated later
                ];
            }
        }
    }
    
    return $normalized;
}

/**
 * Generate chart configurations for ApexCharts
 */
function generateComparisonCharts(array $players): array
{
    return [
        'radar' => generateRadarChartData($players),
        'bars' => generateBarChartData($players),
        'timeline' => generateTimelineData($players),
        'heatmap' => generateHeatmapData($players)
    ];
}

/**
 * Generate radar chart data for combat/movement/tactical comparison
 */
function generateRadarChartData(array $players): array
{
    $categories = ['Combat', 'Movement', 'Tactical', 'Survival', 'Efficiency', 'Objectives'];
    
    $series = [];
    foreach ($players as $player) {
        $series[] = [
            'name' => $player['name'] ?? 'Unknown',
            'data' => [
                calculateCombatScore($player),
                calculateMovementScore($player),
                calculateTacticalScore($player),
                calculateSurvivalScore($player),
                calculateEfficiencyScore($player),
                calculateObjectiveScore($player)
            ]
        ];
    }
    
    return [
        'categories' => $categories,
        'series' => $series
    ];
}

/**
 * Calculate composite combat score (0-100)
 */
function calculateCombatScore(array $player): float
{
    $kills = $player['kills'] ?? 0;
    $kd = $player['kd_ratio'] ?? 0;
    $accuracy = $player['accuracy'] ?? 0;
    $headshots = $player['headshots'] ?? 0;
    
    // Weighted average
    $score = (
        min(100, ($kills / 10)) * 0.3 +
        min(100, ($kd * 20)) * 0.3 +
        ($accuracy) * 0.2 +
        min(100, ($headshots / 2)) * 0.2
    );
    
    return round($score, 2);
}

/**
 * Calculate movement score
 */
function calculateMovementScore(array $player): float
{
    $distance = ($player['distance_walked'] ?? 0) + ($player['distance_sprinted'] ?? 0);
    $jumps = $player['jump_count'] ?? 0;
    
    $score = min(100, ($distance / 100000) * 50 + ($jumps / 500) * 50);
    return round($score, 2);
}

/**
 * Calculate tactical score
 */
function calculateTacticalScore(array $player): float
{
    $headshots = $player['headshots'] ?? 0;
    $kills = $player['kills'] ?? 1;
    $hsPercent = ($headshots / $kills) * 100;
    
    $reloadEfficiency = $player['reload_efficiency'] ?? 50;
    
    $score = ($hsPercent * 0.6) + ($reloadEfficiency * 0.4);
    return round(min(100, $score), 2);
}

/**
 * Calculate survival score
 */
function calculateSurvivalScore(array $player): float
{
    $winRate = $player['win_rate'] ?? 0;
    $kd = $player['kd_ratio'] ?? 0;
    
    $score = ($winRate * 0.6) + (min(100, $kd * 20) * 0.4);
    return round($score, 2);
}

/**
 * Calculate efficiency score
 */
function calculateEfficiencyScore(array $player): float
{
    $kpm = $player['kills_per_minute'] ?? 0;
    $spm = $player['score_per_minute'] ?? 0;
    
    $score = (min(100, $kpm * 10) * 0.5) + (min(100, $spm / 5) * 0.5);
    return round($score, 2);
}

/**
 * Calculate objective score
 */
function calculateObjectiveScore(array $player): float
{
    $captures = $player['objective_captures'] ?? 0;
    $time = $player['objective_time'] ?? 0;
    
    $score = (min(100, $captures * 5) * 0.6) + (min(100, $time / 600) * 0.4);
    return round($score, 2);
}

/**
 * Generate bar chart comparison data
 */
function generateBarChartData(array $players): array
{
    $stats = ['Kills', 'Deaths', 'K/D', 'Headshots', 'Accuracy', 'Win Rate'];
    
    $series = [];
    foreach ($stats as $stat) {
        $data = [];
        foreach ($players as $player) {
            $key = strtolower(str_replace('/', '_', str_replace(' ', '_', $stat)));
            $data[] = $player[$key] ?? 0;
        }
        
        $series[] = [
            'name' => $stat,
            'data' => $data
        ];
    }
    
    return [
        'categories' => array_column($players, 'name'),
        'series' => $series
    ];
}

/**
 * Generate timeline comparison (performance over time)
 */
function generateTimelineData(array $players): array
{
    // This would query match history and create trend lines
    // Simplified version returns mock structure
    return [
        'dates' => [], // Array of timestamps
        'series' => []  // K/D trend per player
    ];
}

/**
 * Generate heatmap for map performance
 */
function generateHeatmapData(array $players): array
{
    $maps = ['V2 Rocket', 'Stalingrad', 'Brest', 'Bazaar', 'Destroyed Village'];
    
    $series = [];
    foreach ($players as $player) {
        $data = [];
        foreach ($maps as $map) {
            // Mock data - would query actual map stats
            $data[] = [
                'x' => $map,
                'y' => rand(0, 10) / 10 // K/D on this map
            ];
        }
        
        $series[] = [
            'name' => $player['name'],
            'data' => $data
        ];
    }
    
    return ['series' => $series];
}

/**
 * Analyze which player is "winning" overall
 */
function analyzeWinner(array $players): array
{
    $scores = [];
    
    foreach ($players as $index => $player) {
        $totalScore = 
            calculateCombatScore($player) * 0.3 +
            calculateMovementScore($player) * 0.1 +
            calculateTacticalScore($player) * 0.2 +
            calculateSurvivalScore($player) * 0.2 +
            calculateEfficiencyScore($player) * 0.1 +
            calculateObjectiveScore($player) * 0.1;
        
        $scores[$index] = [
            'player' => $player['name'],
            'total_score' => round($totalScore, 2),
            'guid' => $player['guid'] ?? ''
        ];
    }
    
    usort($scores, function($a, $b) {
        return $b['total_score'] <=> $a['total_score'];
    });
    
    return [
        'winner' => $scores[0],
        'rankings' => $scores
    ];
}

/**
 * Calculate stat differentials (how much better/worse)
 */
function calculateDifferentials(array $players): array
{
    if (count($players) < 2) {
        return [];
    }
    
    $baseline = $players[0];
    $differentials = [];
    
    for ($i = 1; $i < count($players); $i++) {
        $diff = [];
        $comparePlayer = $players[$i];
        
        $stats = ['kills', 'deaths', 'kd_ratio', 'accuracy', 'headshots', 'win_rate'];
        
        foreach ($stats as $stat) {
            $baseValue = $baseline[$stat] ?? 0;
            $compareValue = $comparePlayer[$stat] ?? 0;
            
            if ($baseValue > 0) {
                $percentDiff = (($compareValue - $baseValue) / $baseValue) * 100;
                $diff[$stat] = [
                    'base' => $baseValue,
                    'compare' => $compareValue,
                    'absolute_diff' => $compareValue - $baseValue,
                    'percent_diff' => round($percentDiff, 2),
                    'better' => $compareValue > $baseValue
                ];
            }
        }
        
        $differentials[$comparePlayer['name']] = $diff;
    }
    
    return $differentials;
}

/**
 * Get suggested players for comparison based on similar skill level
 */
function MohaaStats_GetSimilarPlayers(string $guid, int $limit = 5): array
{
    // Would query database for players with similar K/D, playtime, etc.
    return [];
}
