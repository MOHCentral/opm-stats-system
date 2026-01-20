<?php
/**
 * MOHAA Weapon Details Template
 * 
 * Visual templates for weapon statistics displays
 */

function template_weapon_detail()
{
	global $context, $txt, $scripturl;
	
	$stats = $context['weapon_stats'];
	
	if (!$stats) {
		echo '<div class="errorbox">No data available for this weapon.</div>';
		return;
	}
	
	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<img src="', $settings['images_url'], '/icons/stats.png" alt="*" />
			', htmlspecialchars($_GET['weapon']), ' - Detailed Analysis
		</h3>
	</div>
	
	<div class="windowbg">
		<div class="content">
			<div class="generic_list_wrapper">';
	
	// Combat Performance Card
	echo '
				<div class="stat_card">
					<h4 class="stat_card_title">⚔️ Combat Performance</h4>
					<table class="table_grid">
						<tr>
							<td><strong>Total Kills:</strong></td>
							<td>', number_format($stats['kills'] ?? 0), '</td>
							<td><strong>Accuracy:</strong></td>
							<td>', $stats['accuracy'], '%</td>
						</tr>
						<tr>
							<td><strong>Headshots:</strong></td>
							<td>', number_format($stats['headshots'] ?? 0), ' (', $stats['headshot_pct'], '%)</td>
							<td><strong>K/D Ratio:</strong></td>
							<td>', $stats['kd_ratio'], '</td>
						</tr>
						<tr>
							<td><strong>Shots Fired:</strong></td>
							<td>', number_format($stats['shots_fired'] ?? 0), '</td>
							<td><strong>Shots Hit:</strong></td>
							<td>', number_format($stats['shots_hit'] ?? 0), '</td>
						</tr>
					</table>
				</div>';
	
	// Reload Efficiency Card
	echo '
				<div class="stat_card">
					<h4 class="stat_card_title">🔄 Reload Efficiency</h4>
					<table class="table_grid">
						<tr>
							<td><strong>Total Reloads:</strong></td>
							<td>', number_format($stats['reload_count'] ?? 0), '</td>
							<td><strong>Successful:</strong></td>
							<td>', number_format($stats['reload_done_count'] ?? 0), '</td>
						</tr>
						<tr>
							<td><strong>Reload Efficiency:</strong></td>
							<td class="highlight_stat">', $stats['reload_efficiency'], '%</td>
							<td><strong>Interrupted Reloads:</strong></td>
							<td>', ($stats['reload_count'] ?? 0) - ($stats['reload_done_count'] ?? 0), '</td>
						</tr>
						<tr>
							<td><strong>Avg Ammo Wasted:</strong></td>
							<td>', round($stats['avg_ammo_wasted'], 1), ' rounds</td>
							<td><strong>Tactical Reloads:</strong></td>
							<td>', $stats['tactical_reload_pct'], '%</td>
						</tr>
					</table>
					
					<div id="reload_efficiency_gauge"></div>
				</div>';
	
	// Reload Timing Card
	echo '
				<div class="stat_card">
					<h4 class="stat_card_title">⏱️ Reload Timing</h4>
					<table class="table_grid">
						<tr>
							<td><strong>Average Reload Time:</strong></td>
							<td>', round($stats['avg_reload_time'], 2), 's</td>
							<td><strong>Shots Per Reload:</strong></td>
							<td>', round($stats['shots_per_reload'], 1), '</td>
						</tr>
						<tr>
							<td><strong>Fastest Reload:</strong></td>
							<td class="highlight_good">', round($stats['fastest_reload'], 2), 's</td>
							<td><strong>Slowest Reload:</strong></td>
							<td class="highlight_bad">', round($stats['slowest_reload'], 2), 's</td>
						</tr>
					</table>
					
					<div id="reload_time_distribution"></div>
				</div>';
	
	// ApexCharts visualization
	echo '
			</div>
		</div>
	</div>
	
	<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
	<script>
		// Reload Efficiency Gauge
		var reloadGaugeOptions = {
			series: [', $stats['reload_efficiency'], '],
			chart: {
				type: "radialBar",
				height: 250
			},
			plotOptions: {
				radialBar: {
					hollow: {
						size: "70%"
					},
					dataLabels: {
						name: {
							show: true,
							fontSize: "14px",
							offsetY: -10
						},
						value: {
							show: true,
							fontSize: "30px",
							offsetY: 5,
							formatter: function(val) {
								return val + "%";
							}
						}
					}
				}
			},
			labels: ["Reload Efficiency"],
			colors: ["#00E396"]
		};
		
		var reloadGauge = new ApexCharts(document.querySelector("#reload_efficiency_gauge"), reloadGaugeOptions);
		reloadGauge.render();
		
		// Reload Time Distribution (Histogram)
		var reloadDistOptions = {
			series: [{
				name: "Reload Count",
				data: [5, 12, 18, 25, 15, 8, 3] // Mock data - replace with API data
			}],
			chart: {
				type: "bar",
				height: 300
			},
			plotOptions: {
				bar: {
					borderRadius: 4,
					horizontal: false,
					columnWidth: "85%"
				}
			},
			dataLabels: {
				enabled: false
			},
			xaxis: {
				categories: ["<2.0s", "2.0-2.5s", "2.5-3.0s", "3.0-3.5s", "3.5-4.0s", "4.0-4.5s", ">4.5s"],
				title: {
					text: "Reload Time (seconds)"
				}
			},
			yaxis: {
				title: {
					text: "Frequency"
				}
			},
			title: {
				text: "Reload Time Distribution",
				align: "center"
			},
			colors: ["#008FFB"]
		};
		
		var reloadDist = new ApexCharts(document.querySelector("#reload_time_distribution"), reloadDistOptions);
		reloadDist.render();
	</script>
	
	<style>
		.stat_card {
			background: #f9f9f9;
			padding: 20px;
			margin: 15px 0;
			border-radius: 8px;
			border: 1px solid #ddd;
		}
		.stat_card_title {
			margin: 0 0 15px 0;
			font-size: 18px;
			color: #444;
		}
		.table_grid {
			width: 100%;
			border-collapse: collapse;
		}
		.table_grid td {
			padding: 8px;
			border-bottom: 1px solid #eee;
		}
		.highlight_stat {
			font-size: 20px;
			font-weight: bold;
			color: #00A86B;
		}
		.highlight_good {
			color: #28a745;
			font-weight: bold;
		}
		.highlight_bad {
			color: #dc3545;
			font-weight: bold;
		}
	</style>';
}

function template_player_weapons()
{
	global $context, $scripturl;
	
	$weapons = $context['weapon_list'];
	
	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<img src="', $settings['images_url'], '/icons/weapons.png" alt="*" />
			Arsenal Overview
		</h3>
	</div>
	
	<div class="windowbg">
		<div class="content">
			<table class="table_grid" style="width:100%">
				<thead>
					<tr class="title_bar">
						<th>Weapon</th>
						<th>Kills</th>
						<th>Accuracy</th>
						<th>Headshot %</th>
						<th>Reload Eff.</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>';
	
	foreach ($weapons as $weapon) {
		$accuracy = ($weapon['shots_fired'] ?? 0) > 0 
			? round((($weapon['shots_hit'] ?? 0) / $weapon['shots_fired']) * 100, 1) 
			: 0;
		
		$hsPercent = ($weapon['kills'] ?? 0) > 0 
			? round((($weapon['headshots'] ?? 0) / $weapon['kills']) * 100, 1) 
			: 0;
		
		$reloadEff = ($weapon['reload_count'] ?? 0) > 0 
			? round((($weapon['reload_done_count'] ?? 0) / $weapon['reload_count']) * 100, 1) 
			: 0;
		
		echo '
					<tr>
						<td><strong>', htmlspecialchars($weapon['weapon_name']), '</strong></td>
						<td>', number_format($weapon['kills'] ?? 0), '</td>
						<td>', $accuracy, '%</td>
						<td>', $hsPercent, '%</td>
						<td>', $reloadEff, '%</td>
						<td>
							<a href="', $scripturl, '?action=mohaa_weapon_details&guid=', urlencode($_GET['guid']), '&weapon=', urlencode($weapon['weapon_name']), '">
								🔍 View Details
							</a>
						</td>
					</tr>';
	}
	
	echo '
				</tbody>
			</table>
		</div>
	</div>';
}

function template_weapon_leaderboard()
{
	global $context;
	
	$grouped = $context['weapon_leaderboard'];
	
	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<img src="', $settings['images_url'], '/icons/leaderboard.png" alt="*" />
			Global Weapon Leaderboards
		</h3>
	</div>';
	
	$categoryNames = [
		'rifles' => '🎯 Rifles',
		'submachine_guns' => '⚡ Submachine Guns',
		'heavy' => '💥 Heavy Weapons',
		'pistols' => '🔫 Pistols',
		'grenades' => '💣 Grenades'
	];
	
	foreach ($grouped as $category => $weapons) {
		if (empty($weapons)) continue;
		
		echo '
		<div class="windowbg">
			<div class="content">
				<h4>', $categoryNames[$category], '</h4>
				<table class="table_grid">
					<thead>
						<tr>
							<th>Weapon</th>
							<th>Total Kills</th>
							<th>Avg Accuracy</th>
							<th>Top Player</th>
						</tr>
					</thead>
					<tbody>';
		
		foreach ($weapons as $weapon) {
			echo '
						<tr>
							<td><strong>', htmlspecialchars($weapon['weapon_name']), '</strong></td>
							<td>', number_format($weapon['total_kills'] ?? 0), '</td>
							<td>', round($weapon['avg_accuracy'] ?? 0, 1), '%</td>
							<td>', htmlspecialchars($weapon['top_player'] ?? 'N/A'), '</td>
						</tr>';
		}
		
		echo '
					</tbody>
				</table>
			</div>
		</div>';
	}
}
