# MOHAA Stats Core - Simple Machines Forum Plugin

## Overview

The MOHAA Stats Core plugin integrates game statistics from OpenMOHAA servers into your Simple Machines Forum. It provides:

- **Dashboard**: Global stats overview with live matches, recent activity, top players
- **Leaderboards**: Filterable by stat type, time period, game mode
- **Player Profiles**: Detailed stats, weapon usage, match history, achievements
- **Match Details**: Scoreboard, timeline, heatmaps, weapon breakdown
- **Map Statistics**: Per-map stats with kill/death heatmaps
- **Identity Linking**: Allow forum members to link their game identities
- **Token Authentication**: Generate tokens for seamless game-to-forum login

## Requirements

- Simple Machines Forum 2.1.x or later
- PHP 7.4 or later with cURL extension
- MOHAA Stats API server (Go backend)

## Installation

1. Go to **Admin** → **Package Manager** → **Download Packages**
2. Upload the `mohaa_stats_core.tar.gz` package
3. Click **Install** and follow the prompts
4. Go to **Admin** → **MOHAA Stats** to configure

## Configuration

### General Settings

- **Enable MOHAA Stats**: Toggle the entire stats system on/off
- **Menu title**: Customize the menu button text
- **Show stats on profiles**: Display game stats on member profile pages
- **Allow identity linking**: Enable/disable the identity linking feature

### API Connection

- **API Base URL**: URL of your MOHAA Stats API server (e.g., `http://localhost:8080`)
- **Server Token**: Authentication token for API requests
- **Request Timeout**: Timeout for API requests in seconds

### Caching

- **Cache Duration**: How long to cache general API responses (in seconds)
- **Live Cache Duration**: How long to cache live match data (shorter is better)

### Identity Linking

- **Max Identities**: Maximum game identities a member can link
- **Claim Code Expiry**: How long claim codes are valid
- **Token Expiry**: How long game tokens are valid

## Usage

### For Forum Members

1. Navigate to **Game Stats** in the menu
2. Browse leaderboards, matches, and maps
3. Click on player names to view detailed profiles
4. Go to **Link Game Identity** to connect your game profile
5. Use **Generate Token** to get a one-time login code for the game

### In-Game Commands

Once the tracker.scr is installed on your game server:

```
/login YOUR_TOKEN    - Login with your forum token
/claim YOUR_CODE     - Link your game identity to your forum account
/stats              - View your current stats
```

## Template Customization

Templates are located in `Themes/default/`:

- `MohaaStats.template.php` - Main dashboard
- `MohaaStatsPlayer.template.php` - Player profile
- `MohaaStatsMatch.template.php` - Match details
- `MohaaStatsLeaderboard.template.php` - Leaderboard pages

CSS styles: `Themes/default/css/mohaa_stats.css`
JavaScript: `Themes/default/scripts/mohaa_stats.js`

## Hooks Available

Other SMF mods can hook into MOHAA Stats:

- `integrate_mohaa_stats_player_loaded` - When a player profile is loaded
- `integrate_mohaa_stats_match_loaded` - When match details are loaded
- `integrate_mohaa_stats_identity_linked` - When an identity is linked

## Troubleshooting

### API Connection Failed

1. Check that the API server is running
2. Verify the API URL is correct (include http:// or https://)
3. Check the server token matches the API configuration
4. Ensure your server can reach the API (firewall, etc.)

### Cache Issues

Use the **Clear Cache** button in Admin settings to refresh all cached data.

### No Data Showing

1. Ensure the game server is sending data to the API
2. Check the API logs for errors
3. Verify database connectivity

## Support

For issues and feature requests, visit:
https://github.com/your-repo/mohaa-stats

## License

MIT License
