# MOHAA Stats - Simple Machines Forum Integration

## Overview

The MOHAA Stats system integrates with Simple Machines Forum (SMF) through a series of plugins (called "Mods" in SMF). This approach:

- Leverages SMF's existing user management and authentication
- Uses SMF's templating and theming system
- Integrates stats display into the forum community
- Provides SSI (Server Side Includes) for embedding stats elsewhere

## Plugin Structure

```
smf-plugins/
├── mohaa_stats_core/          # Core integration - API, caching, base functionality
├── mohaa_stats_profile/       # Player profile integration on forum profiles
├── mohaa_stats_leaderboards/  # Leaderboard pages and blocks
├── mohaa_stats_matches/       # Match history and details
├── mohaa_stats_heatmaps/      # Kill/death heatmap visualizations
├── mohaa_stats_achievements/  # Achievement system display
├── mohaa_stats_live/          # Live match tracking widget
├── mohaa_stats_tournaments/   # Tournament brackets and registration
└── mohaa_stats_linking/       # Game identity linking to forum accounts
```

## Installation

1. Upload each plugin folder to `./Packages/` on your SMF installation
2. Go to Admin → Packages → Browse Packages
3. Install each plugin in order (core first)
4. Configure API endpoint in Admin → Configuration → MOHAA Stats

## Requirements

- SMF 2.1.x
- PHP 8.0+
- cURL extension enabled
- MOHAA Stats API running

## API Configuration

After installing `mohaa_stats_core`, configure:

- **API Base URL**: `http://your-api-server:8080`
- **Server Token**: Your server's authentication token
- **Cache Duration**: How long to cache API responses (default: 60 seconds)
- **Rate Limit**: Max API requests per minute (default: 100)

## Theming

Plugins use SMF's template system. Custom templates are in:
- `Themes/default/MohaaStats/`

CSS files:
- `Themes/default/css/mohaa_stats.css`

JavaScript:
- `Themes/default/scripts/mohaa_stats.js`
