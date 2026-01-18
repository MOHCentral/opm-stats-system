<?php
/**
 * MOHAA Stats Dashboard
 */

// API Configuration
define('MOHAA_API_URL', getenv('MOHAA_API_URL') ?: 'http://172.17.0.1:8080');

function api_request($endpoint) {
    $url = rtrim(MOHAA_API_URL, '/') . '/' . ltrim($endpoint, '/');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        return json_decode($response, true);
    }
    return null;
}

$api_health = api_request('/health');
$leaderboard_response = api_request('/api/v1/leaderboard?limit=10&stat=kills');
$leaderboard = is_array($leaderboard_response) ? ($leaderboard_response['players'] ?? $leaderboard_response['data'] ?? $leaderboard_response) : [];
$api_connected = ($api_health !== null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOHAA Stats Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --mohaa-green: #4a7c23; --mohaa-brown: #8b6914; }
        body { background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: #f0f0f0; min-height: 100vh; }
        .navbar { background: rgba(0,0,0,0.8) !important; border-bottom: 2px solid var(--mohaa-green); }
        .card { background: rgba(40,40,40,0.95); border: 1px solid #444; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .card-header { background: linear-gradient(90deg, var(--mohaa-green), var(--mohaa-brown)); font-weight: bold; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value { font-size: 2.5rem; font-weight: bold; color: var(--mohaa-green); }
        .table-dark { --bs-table-bg: transparent; }
        .rank-1 { color: gold; } .rank-2 { color: silver; } .rank-3 { color: #cd7f32; }
        .status-online { color: #00ff00; } .status-offline { color: #ff4444; }
        .nav-tabs .nav-link { color: #aaa; } .nav-tabs .nav-link.active { background: var(--mohaa-green); color: white; }
        footer { border-top: 1px solid #444; background: rgba(0,0,0,0.5); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark"><div class="container">
<a class="navbar-brand" href="#"><i class="bi bi-crosshair"></i> MOHAA Stats Dashboard</a>
<span class="nav-item nav-link ms-auto">API: <?php if($api_connected):?><span class="status-online"><i class="bi bi-circle-fill"></i> Connected</span><?php else:?><span class="status-offline"><i class="bi bi-circle-fill"></i> Offline</span><?php endif;?></span>
</div></nav>

<div class="container py-4">
<!-- Stats Overview -->
<div class="row mb-4">
<div class="col-md-3"><div class="card stat-card text-center p-3">
    <i class="bi bi-people-fill fs-1 text-info"></i>
    <div class="stat-value"><?php echo is_array($leaderboard) ? count($leaderboard) : '0'; ?></div>
    <div class="text-muted">Players Tracked</div>
</div></div>
<div class="col-md-3"><div class="card stat-card text-center p-3">
    <i class="bi bi-hdd-rack-fill fs-1 text-warning"></i>
    <div class="stat-value">1</div>
    <div class="text-muted">Active Servers</div>
</div></div>
<div class="col-md-3"><div class="card stat-card text-center p-3">
    <i class="bi bi-bullseye fs-1 text-danger"></i>
    <div class="stat-value"><?php 
        $t = 0; 
        if (is_array($leaderboard)) {
            foreach($leaderboard as $p) {
                $t += $p['kills'] ?? 0;
            }
        }
        echo number_format($t); 
    ?></div>
    <div class="text-muted">Total Kills</div>
</div></div>
<div class="col-md-3"><div class="card stat-card text-center p-3">
    <i class="bi bi-trophy-fill fs-1 text-success"></i>
    <div class="stat-value">75+</div>
    <div class="text-muted">Achievements</div>
</div></div>
</div>

<!-- Leaderboard -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-trophy"></i> Top Players Leaderboard</div>
    <div class="card-body">
    <?php if (is_array($leaderboard) && count($leaderboard) > 0): ?>
    <table class="table table-dark table-hover mb-0">
        <thead><tr><th>Rank</th><th>Player</th><th>Kills</th><th>Deaths</th><th>K/D Ratio</th><th>Score</th></tr></thead>
        <tbody>
        <?php foreach($leaderboard as $i => $p): $r = $i + 1; ?>
        <tr>
            <td><?php 
                if ($r == 1) echo '<i class="bi bi-trophy-fill rank-1"></i> 1st';
                elseif ($r == 2) echo '<i class="bi bi-trophy-fill rank-2"></i> 2nd';
                elseif ($r == 3) echo '<i class="bi bi-trophy-fill rank-3"></i> 3rd';
                else echo $r;
            ?></td>
            <td><strong><?php echo htmlspecialchars($p['name'] ?? $p['player_name'] ?? 'Unknown'); ?></strong></td>
            <td><?php echo number_format($p['kills'] ?? 0); ?></td>
            <td><?php echo number_format($p['deaths'] ?? 0); ?></td>
            <td><?php 
                $d = $p['deaths'] ?? 1; 
                echo number_format(($d > 0) ? ($p['kills'] ?? 0) / $d : 0, 2); 
            ?></td>
            <td><?php echo number_format($p['score'] ?? 0); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle"></i> No player data available yet. Stats will appear once players connect to the game server and tracker.scr records their activity.
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- System Status & Quick Links -->
<div class="row">
<div class="col-md-6">
    <div class="card">
        <div class="card-header"><i class="bi bi-gear"></i> System Status</div>
        <div class="card-body">
        <ul class="list-group list-group-flush" style="background:transparent;">
            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                <span><i class="bi bi-database"></i> MOHAA Stats API</span>
                <?php if($api_connected): ?><span class="badge bg-success">Online</span>
                <?php else: ?><span class="badge bg-danger">Offline</span><?php endif; ?>
            </li>
            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                <span><i class="bi bi-hdd"></i> MariaDB Database</span>
                <span class="badge bg-success">Online</span>
            </li>
            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                <span><i class="bi bi-graph-up"></i> Prometheus Metrics</span>
                <span class="badge bg-success">Collecting</span>
            </li>
            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                <span><i class="bi bi-bar-chart-line"></i> Grafana Dashboards</span>
                <span class="badge bg-success">Available</span>
            </li>
        </ul>
        </div>
    </div>
</div>
<div class="col-md-6">
    <div class="card">
        <div class="card-header"><i class="bi bi-link-45deg"></i> Quick Links</div>
        <div class="card-body">
        <div class="d-grid gap-2">
            <a href="http://localhost:3000" target="_blank" class="btn btn-outline-warning">
                <i class="bi bi-bar-chart-line"></i> Grafana Dashboards
            </a>
            <a href="http://localhost:8889" target="_blank" class="btn btn-outline-info">
                <i class="bi bi-database"></i> phpMyAdmin (Database)
            </a>
            <a href="http://localhost:9090" target="_blank" class="btn btn-outline-danger">
                <i class="bi bi-graph-up"></i> Prometheus Metrics
            </a>
            <a href="http://localhost:8080/health" target="_blank" class="btn btn-outline-success">
                <i class="bi bi-heart-pulse"></i> API Health Check
            </a>
        </div>
        </div>
    </div>
</div>
</div>

<!-- Debug Info -->
<div class="card mt-4">
    <div class="card-header"><i class="bi bi-terminal"></i> Debug Information</div>
    <div class="card-body">
        <pre class="text-light mb-0" style="font-size: 0.85rem;">
API URL: <?php echo MOHAA_API_URL; ?>

Health Check Response:
<?php print_r($api_health); ?>

Leaderboard Response:
<?php print_r($leaderboard_response); ?>
        </pre>
    </div>
</div>
</div>

<footer class="py-3 mt-4">
    <div class="container text-center text-muted">
        <p class="mb-0">MOHAA Stats System &copy; <?php echo date('Y'); ?> | Powered by OpenMoHAA</p>
        <small>API Endpoint: <?php echo MOHAA_API_URL; ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
