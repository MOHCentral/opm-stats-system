<?php
/**
 * MOHAA Stats - Language Strings (English)
 *
 * @package MohaaStats
 * @version 1.0.0
 */

// Menu and general
$txt['mohaa_stats'] = 'Game Stats';
$txt['mohaa_leaderboards'] = 'Leaderboards';
$txt['mohaa_matches'] = 'Matches';
$txt['mohaa_maps'] = 'Maps';
$txt['mohaa_live'] = 'Live Matches';
$txt['mohaa_link_identity'] = 'Link Game Identity';

// Page titles
$txt['mohaa_player_title'] = '%s - Player Stats';
$txt['mohaa_match_title'] = 'Match on %s';

// Admin
$txt['mohaa_stats_admin'] = 'MOHAA Stats';
$txt['mohaa_stats_admin_desc'] = 'Configure the MOHAA game statistics integration.';
$txt['mohaa_stats_general'] = 'General Settings';
$txt['mohaa_stats_api_settings'] = 'API Connection';
$txt['mohaa_stats_cache'] = 'Caching';
$txt['mohaa_stats_linking'] = 'Identity Linking';

// Settings
$txt['mohaa_stats_enabled'] = 'Enable MOHAA Stats';
$txt['mohaa_stats_menu_title'] = 'Menu title';
$txt['mohaa_stats_show_in_profile'] = 'Show stats on member profiles';
$txt['mohaa_stats_allow_linking'] = 'Allow members to link game identities';
$txt['mohaa_stats_display_options'] = 'Display Options';
$txt['mohaa_stats_leaderboard_limit'] = 'Players per leaderboard page';
$txt['mohaa_stats_recent_matches_limit'] = 'Recent matches to display';
$txt['mohaa_stats_show_heatmaps'] = 'Show kill/death heatmaps';
$txt['mohaa_stats_show_achievements'] = 'Show achievements';

// API settings
$txt['mohaa_stats_api_url'] = 'API Base URL';
$txt['mohaa_stats_server_token'] = 'Server authentication token';
$txt['mohaa_stats_api_timeout'] = 'API request timeout';
$txt['mohaa_stats_rate_limiting'] = 'Rate Limiting';
$txt['mohaa_stats_rate_limit'] = 'Maximum API requests';
$txt['mohaa_seconds'] = 'seconds';
$txt['mohaa_minutes'] = 'minutes';
$txt['mohaa_per_minute'] = 'per minute';

// Cache settings
$txt['mohaa_stats_cache_duration'] = 'Cache duration (general)';
$txt['mohaa_stats_live_cache_duration'] = 'Cache duration (live data)';
$txt['mohaa_stats_cache_leaderboards'] = 'Cache leaderboard data';
$txt['mohaa_stats_cache_player_stats'] = 'Cache player statistics';
$txt['mohaa_clear_cache'] = 'Clear Cache';
$txt['mohaa_cache_cleared'] = 'API cache has been cleared.';

// Linking settings
$txt['mohaa_stats_max_identities'] = 'Maximum identities per member';
$txt['mohaa_stats_claim_expiry'] = 'Claim code expiry time';
$txt['mohaa_stats_token_expiry'] = 'Game token expiry time';

// API test
$txt['mohaa_test_connection'] = 'Test Connection';
$txt['mohaa_api_test_success'] = 'API connection successful!';
$txt['mohaa_api_test_failed'] = 'API connection failed. Check URL and token.';

// Stats labels
$txt['mohaa_kills'] = 'Kills';
$txt['mohaa_deaths'] = 'Deaths';
$txt['mohaa_kd_ratio'] = 'K/D Ratio';
$txt['mohaa_headshots'] = 'Headshots';
$txt['mohaa_accuracy'] = 'Accuracy';
$txt['mohaa_matches_played'] = 'Matches Played';
$txt['mohaa_playtime'] = 'Playtime';
$txt['mohaa_score'] = 'Score';
$txt['mohaa_rank'] = 'Rank';

// Leaderboard
$txt['mohaa_leaderboard_stat'] = 'Stat';
$txt['mohaa_leaderboard_period'] = 'Period';
$txt['mohaa_period_all'] = 'All Time';
$txt['mohaa_period_month'] = 'This Month';
$txt['mohaa_period_week'] = 'This Week';
$txt['mohaa_period_day'] = 'Today';
$txt['mohaa_stat_kills'] = 'Kills';
$txt['mohaa_stat_kd'] = 'K/D Ratio';
$txt['mohaa_stat_score'] = 'Score';
$txt['mohaa_stat_headshots'] = 'Headshots';
$txt['mohaa_stat_accuracy'] = 'Accuracy';
$txt['mohaa_stat_playtime'] = 'Playtime';

// Player page
$txt['mohaa_player_overview'] = 'Overview';
$txt['mohaa_player_weapons'] = 'Weapons';
$txt['mohaa_player_matches'] = 'Match History';
$txt['mohaa_player_heatmaps'] = 'Heatmaps';
$txt['mohaa_player_achievements'] = 'Achievements';
$txt['mohaa_last_seen'] = 'Last seen';
$txt['mohaa_verified_player'] = 'Verified';

// Match page
$txt['mohaa_match_scoreboard'] = 'Scoreboard';
$txt['mohaa_match_timeline'] = 'Timeline';
$txt['mohaa_match_kills'] = 'Kill Feed';
$txt['mohaa_match_heatmap'] = 'Heatmap';
$txt['mohaa_match_stats'] = 'Statistics';
$txt['mohaa_team_allies'] = 'Allies';
$txt['mohaa_team_axis'] = 'Axis';
$txt['mohaa_match_duration'] = 'Duration';
$txt['mohaa_total_kills'] = 'Total Kills';
$txt['mohaa_avg_accuracy'] = 'Avg Accuracy';

// Maps
$txt['mohaa_map_stats'] = 'Map Statistics';
$txt['mohaa_popular_weapon'] = 'Popular Weapon';
$txt['mohaa_avg_kill_distance'] = 'Avg Kill Distance';
$txt['mohaa_hs_rate'] = 'Headshot Rate';

// Heatmaps
$txt['mohaa_heatmap_kills'] = 'Kill Locations';
$txt['mohaa_heatmap_deaths'] = 'Death Locations';
$txt['mohaa_heatmap_both'] = 'Both';

// Live matches
$txt['mohaa_no_live_matches'] = 'No live matches at the moment.';
$txt['mohaa_players_online'] = 'players online';

// Identity linking
$txt['mohaa_linked_identities'] = 'Linked Game Identities';
$txt['mohaa_no_identities'] = 'You have no linked game identities.';
$txt['mohaa_generate_token'] = 'Generate Game Token';
$txt['mohaa_generate_claim'] = 'Generate Claim Code';
$txt['mohaa_token_instructions'] = 'Use this token in-game with the <code>login</code> command:';
$txt['mohaa_claim_instructions'] = 'Use this code in-game with the <code>claim</code> command to link your identity:';
$txt['mohaa_token_expires'] = 'Token expires in';
$txt['mohaa_unlink'] = 'Unlink';
$txt['mohaa_unlink_confirm'] = 'Are you sure you want to unlink this game identity?';

// Errors
$txt['mohaa_stats_disabled'] = 'The game statistics system is currently disabled.';
$txt['mohaa_player_not_found'] = 'Player not found.';
$txt['mohaa_match_not_found'] = 'Match not found.';
$txt['mohaa_map_not_found'] = 'Map not found.';
$txt['mohaa_api_error'] = 'Could not fetch data from the stats server.';

// Weapons
$txt['mohaa_weapon_rifle'] = 'Rifles';
$txt['mohaa_weapon_smg'] = 'SMGs';
$txt['mohaa_weapon_sniper'] = 'Sniper Rifles';
$txt['mohaa_weapon_pistol'] = 'Pistols';
$txt['mohaa_weapon_shotgun'] = 'Shotguns';
$txt['mohaa_weapon_mg'] = 'Machine Guns';
$txt['mohaa_weapon_explosive'] = 'Explosives';
$txt['mohaa_weapon_melee'] = 'Melee';

// Achievements
$txt['mohaa_achievement_unlocked'] = 'Unlocked';
$txt['mohaa_achievement_locked'] = 'Locked';
$txt['mohaa_achievement_progress'] = 'Progress';

// Additional labels
$txt['mohaa_player_profile'] = 'Player Profile';
$txt['mohaa_first_seen'] = 'First seen';
$txt['mohaa_kd'] = 'K/D';
$txt['mohaa_recent_matches'] = 'Recent Matches';
$txt['mohaa_weapon_stats'] = 'Weapon Statistics';
$txt['mohaa_achievements'] = 'Achievements';
$txt['mohaa_performance'] = 'Performance';
$txt['mohaa_no_matches'] = 'No matches found.';
$txt['mohaa_no_data'] = 'No data available.';
$txt['mohaa_no_achievements'] = 'No achievements yet.';
$txt['mohaa_weapon'] = 'Weapon';
$txt['mohaa_performance_chart'] = 'Performance Over Time';
$txt['mohaa_kd_trend'] = 'K/D Trend';
$txt['mohaa_match_detail'] = 'Match Detail';
$txt['mohaa_map'] = 'Map';
$txt['mohaa_mode'] = 'Mode';
$txt['mohaa_duration'] = 'Duration';
$txt['mohaa_players'] = 'Players';
$txt['mohaa_date'] = 'Date';
$txt['mohaa_scoreboard'] = 'Scoreboard';
$txt['mohaa_heatmap'] = 'Heatmap';
$txt['mohaa_timeline'] = 'Timeline';
$txt['mohaa_weapons'] = 'Weapons';
$txt['mohaa_player'] = 'Player';
$txt['mohaa_weapon_breakdown'] = 'Weapon Breakdown';
$txt['mohaa_leaderboard'] = 'Leaderboard';
$txt['mohaa_all_modes'] = 'All Modes';
$txt['mohaa_sort_by'] = 'Sort By';
$txt['mohaa_period'] = 'Period';
$txt['mohaa_all_time'] = 'All Time';
$txt['mohaa_this_month'] = 'This Month';
$txt['mohaa_this_week'] = 'This Week';
$txt['mohaa_today'] = 'Today';
$txt['mohaa_weapon_leaderboard'] = 'Weapon Leaderboard';
$txt['mohaa_avg_accuracy'] = 'Average Accuracy';
$txt['mohaa_top_players_map'] = 'Top Players on This Map';
$txt['mohaa_identity_already_linked'] = 'Your game identity is already linked.';
$txt['mohaa_linked_player'] = 'Linked Player';
$txt['mohaa_linked_since'] = 'Linked Since';
$txt['mohaa_link_instructions'] = 'To link your game identity, generate a claim code below and enter it in-game.';
$txt['mohaa_your_claim_code'] = 'Your Claim Code';
$txt['mohaa_claim_expires'] = 'Code Expires';
$txt['mohaa_game_token'] = 'Game Token';
$txt['mohaa_must_link_first'] = 'You must link your game identity before generating a token.';
$txt['mohaa_token_description'] = 'Generate a one-time token to login to the game with your forum account.';
$txt['mohaa_your_token'] = 'Your Game Token';
$txt['mohaa_token_instructions'] = 'Type this token in the game console using: login YOUR_TOKEN';
$txt['mohaa_token_expires'] = 'Token Expires';
$txt['mohaa_live_matches'] = 'Live Matches';
$txt['mohaa_recent_activity'] = 'Recent Activity';
$txt['mohaa_top_players'] = 'Top Players';
$txt['mohaa_activity_chart'] = 'Activity Chart';
$txt['mohaa_weapon_distribution'] = 'Weapon Distribution';
$txt['mohaa_server_status'] = 'Server Status';
$txt['mohaa_view_all'] = 'View All';
