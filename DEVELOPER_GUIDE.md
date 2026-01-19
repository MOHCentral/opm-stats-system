# MOHAA Stats SMF Integration - Developer Documentation

## Project Overview

This project integrates Medal of Honor: Allied Assault (MOHAA) game statistics into a Simple Machines Forum (SMF) installation. Players can view leaderboards, match history, achievements, server browsers, and tournaments - all within the forum.

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         SMF Forum                                │
│                     (localhost:8888)                             │
├─────────────────────────────────────────────────────────────────┤
│  MohaaPlayers.php (Sources/)                                     │
│  ├── Action Handlers (mohaadashboard, mohaaleaderboard, etc.)   │
│  ├── Menu Button Integration                                     │
│  └── Profile Area Hooks                                          │
├─────────────────────────────────────────────────────────────────┤
│  Templates (Themes/default/)                                     │
│  ├── MohaaDashboard.template.php    - War Room / Overview        │
│  ├── MohaaLeaderboard.template.php  - Player Rankings            │
│  ├── MohaaMatches.template.php      - Live & Recent Matches      │
│  ├── MohaaMaps.template.php         - Map Statistics             │
│  ├── MohaaPlayer.template.php       - Player Profile + Compare   │
│  ├── MohaaProfile.template.php      - SMF Profile Integration    │
│  ├── MohaaAchievements.template.php - Medal Cabinet              │
│  ├── MohaaServers.template.php      - Server Browser             │
│  └── MohaaTournaments.template.php  - Tournament System          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Go Stats API                                │
│               (mohaa-stats-api - Port 8080)                      │
│  ├── /api/players      - Player data                             │
│  ├── /api/matches      - Match history                           │
│  ├── /api/servers      - Server status                           │
│  └── /api/leaderboard  - Rankings                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    OpenMOHAA Game Server                         │
│                      (UDP Port 12203)                            │
│  └── Sends events via UDP to stats API                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## Docker Environment

### Containers

| Container | Image | Port | Purpose |
|-----------|-------|------|---------|
| smf-smf-1 | Custom SMF | 8888 | Forum web server |
| smf-smf-db-1 | MariaDB | 3306 | Database |

### Credentials

- **Database**: root / root_password
- **Database Name**: smf
- **SMF User**: elgan (member ID: 1)

### Docker Commands

```bash
# Start containers
cd /home/elgan/.local/share/openmohaa/main/mohaa-stats-api/smf
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker logs smf-smf-1

# Apache error logs (no pipe to avoid TTY issues)
docker exec smf-smf-1 tail -n 100 /var/log/apache2/error.log

# Shell into container
docker exec -it smf-smf-1 bash

# Restart
docker restart smf-smf-1
```

---

## SMF Plugin System

### How SMF Hooks Work

SMF uses a hook system where you register callback functions in the database. When SMF reaches a hook point, it calls all registered functions.

**Hooks we use:**

1. **integrate_actions** - Register new page actions (URLs)
2. **integrate_menu_buttons** - Add items to navigation menu
3. **integrate_profile_areas** - Add sections to user profiles

### Database Hook Registration

Hooks are stored in `smf_settings` table:

```sql
SELECT * FROM smf_settings WHERE variable LIKE 'integrate_%';
```

Our registered hooks:
```
integrate_actions = MohaaPlayers.php|MohaaPlayers_Actions
integrate_menu_buttons = MohaaPlayers.php|MohaaPlayers_MenuButtons
integrate_profile_areas = MohaaPlayers.php|MohaaPlayers_ProfileAreas
```

### MohaaPlayers.php Structure

Location: `/var/www/html/Sources/MohaaPlayers.php`

```php
<?php
// Action registration - maps URLs to handler functions
function MohaaPlayers_Actions(&$actionArray) {
    $actionArray["mohaadashboard"] = array("MohaaPlayers.php", "MohaaPlayers_Dashboard");
    $actionArray["mohaaleaderboard"] = array("MohaaPlayers.php", "MohaaPlayers_Leaderboard");
    // ... more actions
}

// Menu button - adds MOHAA Stats to navigation
function MohaaPlayers_MenuButtons(&$buttons) {
    global $scripturl;
    $buttons["mohaastats"] = array(
        "title" => "MOHAA Stats",
        "href" => $scripturl . "?action=mohaadashboard",
        "sub_buttons" => array(/* dropdown items */)
    );
}

// Profile areas - adds tabs to user profile
function MohaaPlayers_ProfileAreas(&$profile_areas) {
    // Adds MOHAA Stats and Link Game Identity
}

// Page handler example
function MohaaPlayers_Dashboard() {
    global $context;
    loadTemplate("MohaaDashboard");
    $context["page_title"] = "MOHAA War Room";
    $context["sub_template"] = "mohaa_dashboard";
}
```

### Template Naming Convention

**CRITICAL**: The `sub_template` value must match a function named `template_{sub_template}()` in the loaded template file.

| Action | loadTemplate() | sub_template | Required Function |
|--------|---------------|--------------|-------------------|
| mohaadashboard | MohaaDashboard | mohaa_dashboard | template_mohaa_dashboard() |
| mohaaleaderboard | MohaaLeaderboard | mohaa_leaderboard | template_mohaa_leaderboard() |
| mohaamatches | MohaaMatches | mohaa_matches | template_mohaa_matches() |
| mohaamaps | MohaaMaps | mohaa_maps | template_mohaa_maps() |
| mohaaplayer | MohaaPlayer | mohaa_player | template_mohaa_player() |
| mohaacompare | MohaaPlayer | mohaa_compare | template_mohaa_compare() |
| mohaaservers | MohaaServers | mohaa_servers_list | template_mohaa_servers_list() |
| mohaaachievements | MohaaAchievements | mohaa_achievements_list | template_mohaa_achievements_list() |
| mohaatournaments | MohaaTournaments | mohaa_tournaments_list | template_mohaa_tournaments_list() |

---

## Database Schema

### smf_mohaa_claims

Stores temporary tokens for linking game identity to forum account.

```sql
CREATE TABLE smf_mohaa_claims (
    id_member INT PRIMARY KEY,
    claim_token VARCHAR(64) NOT NULL,
    expires INT NOT NULL
);
```

**Flow:**
1. User visits Profile → Link Game Identity
2. Clicks "Generate Token" - creates 32-char hex token, expires in 10 mins
3. User runs `/login TOKEN` in game
4. Game server calls `?action=mohaaclaims` with token + player GUID
5. Token validated, GUID linked to member in smf_mohaa_identities

### smf_mohaa_identities

Links forum accounts to game player GUIDs.

```sql
CREATE TABLE smf_mohaa_identities (
    id_identity INT AUTO_INCREMENT PRIMARY KEY,
    id_member INT NOT NULL,
    player_guid VARCHAR(64) NOT NULL,
    player_name VARCHAR(64),
    linked_date INT NOT NULL
);
```

---

## Template Files

### Location

- **Container**: `/var/www/html/Themes/default/`
- **Local Plugin Sources**: `/home/elgan/.local/share/openmohaa/main/mohaa-stats-api/smf-plugins/`

### Template Structure

```php
<?php
// Template file: MohaaExample.template.php

function template_mohaa_example()
{
    global $context, $scripturl, $txt;
    
    // Output HTML directly with echo
    echo '
    <style>
        /* Scoped CSS */
        .mohaa-container { ... }
    </style>
    
    <div class="mohaa-container">
        <h1>Page Title</h1>
        <!-- Content -->
    </div>
    
    <script>
        // JavaScript
    </script>';
}
```

### Design Theme ("Hybrid Design")

We use a **Hybrid Design** philosophy for the War Room and dashboards:

- **Clean Grid Layout**: Use CSS Grid for responsive, organized dashboards (`.mohaa-grid`).
- **SMF Layout Integration**: Use SMF native classes for containers (`windowbg`, `roundframe`, `cat_bar`) to ensure seamless integration with the forum theme.
- **Minimal Custom CSS**: Use custom CSS *only* for:
  - Grid positioning
  - Card hover effects & boxing
  - Specific graphical elements (SVG gauges, silhouettes, weapons)
- **Colors**:
  - Prefer Theme Variables: Use parent theme colors where possible.
  - Functional Accents: Use standard red/green/orange for status (Win/Loss/Draw) but avoid overriding global backgrounds.

- **Typography**: Inherit forum fonts (`Verdana`, `Segoe UI`, etc.) rather than forcing `Courier New`.

### ApexCharts Integration

We use ApexCharts for data visualization:

```html
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div id="myChart"></div>
<script>
var options = {
    chart: { type: 'bar', height: 300 },
    series: [{ data: [1,2,3] }],
    // ...
};
new ApexCharts(document.querySelector("#myChart"), options).render();
</script>
```

---

## Current Issues & Debugging

### Known Problems (as of Jan 18, 2026)

1. **Servers page shows "0 0 0 0"** - Template function may not match, or CSS is broken

2. **Dashboard cards empty** - War Room layout rendering but content sections not populating

3. **Achievements sa=recent error** - Sub-actions not handled in MohaaPlayers.php

4. **Tournaments minimal content** - Template function mismatch likely

5. **Profile areas not showing** - Hook may not be firing, or permission issue

### Debugging Steps

```bash
# 1. Check PHP syntax errors
docker exec smf-smf-1 php -l /var/www/html/Sources/MohaaPlayers.php
docker exec smf-smf-1 php -l /var/www/html/Themes/default/MohaaServers.template.php

# 2. Check Apache error logs
docker exec smf-smf-1 tail -n 100 /var/log/apache2/error.log

# 3. Verify template functions exist
docker exec smf-smf-1 grep "function template_" /var/www/html/Themes/default/MohaaServers.template.php

# 4. Check hooks are registered
docker exec smf-smf-db-1 mysql -uroot -proot_password -e "SELECT * FROM smf.smf_settings WHERE variable LIKE 'integrate_%';"

# 5. Verify sub_template matches function
docker exec smf-smf-1 grep -n "sub_template" /var/www/html/Sources/MohaaPlayers.php
```

### Common Fixes

**Template function name mismatch:**
```bash
docker exec smf-smf-1 sed -i 's/function template_main()/function template_mohaa_servers_list()/' /var/www/html/Themes/default/MohaaServers.template.php
```

**PHP syntax error:**
Look for unclosed brackets, mismatched quotes, missing semicolons.

**Hook not firing:**
Re-register in database:
```sql
INSERT INTO smf_settings (variable, value) VALUES ('integrate_actions', 'MohaaPlayers.php|MohaaPlayers_Actions')
ON DUPLICATE KEY UPDATE value = VALUES(value);
```

---

## Development Workflow

### Plugin File Structure (SINGLE SOURCE OF TRUTH)

All MOHAA plugin code lives in `smf-mohaa/`:

```
smf-mohaa/
├── Sources/                    # PHP source files
│   ├── MohaaAchievements.php
│   ├── MohaaPlayers.php
│   ├── MohaaServers.php
│   ├── MohaaTeams.php
│   ├── MohaaTournaments.php
│   └── MohaaStats/             # Core stats module
│       ├── MohaaStats.php
│       ├── MohaaStatsAPI.php
│       └── MohaaStatsAdmin.php
└── Themes/default/             # Template files
    ├── *.template.php          # All templates
    └── languages/              # Language files
```

### How It Works

1. `smf-mohaa/` is mounted at `/mohaa` in the container
2. `entrypoint.sh` creates symlinks from `/mohaa` to SMF directories
3. **Edit files locally → Changes are instant!** (no docker cp needed)

### Making Template Changes

1. **Edit locally** in `smf-mohaa/Themes/default/`
2. **Refresh browser** - changes are immediate via symlinks
3. **Verify syntax if needed:**
   ```bash
   docker exec smf-smf-1 php -l /var/www/html/Themes/default/Template.template.php
   ```

### Making PHP Changes

1. **Edit locally** in `smf-mohaa/Sources/`
2. **Refresh browser** - PHP files are interpreted on each request
3. **Verify syntax:**
   ```bash
   docker exec smf-smf-1 php -l /var/www/html/Sources/MohaaPlayers.php
   ```

### Adding New Actions

1. **Register action in MohaaPlayers_Actions():**
   ```php
   $actionArray["mohaaXXX"] = array("MohaaPlayers.php", "MohaaPlayers_XXX");
   ```

2. **Create handler function:**
   ```php
   function MohaaPlayers_XXX() {
       global $context;
       loadTemplate("MohaaXXX");
       $context["page_title"] = "Title";
       $context["sub_template"] = "mohaa_xxx";
   }
   ```

3. **Create template file** with `template_mohaa_xxx()` function

4. **Add to menu** in MohaaPlayers_MenuButtons() if desired

---

## API Integration (TODO)

### Go Stats API Endpoints

The Go API runs on port 8080 and provides:

```
GET /api/players              - List all players
GET /api/players/:guid        - Single player stats
GET /api/leaderboard          - Top players
GET /api/matches              - Recent matches
GET /api/matches/:id          - Match details
GET /api/servers              - Server status
GET /api/maps                 - Map statistics
```

### Fetching Data in Templates

```javascript
fetch('http://localhost:8080/api/leaderboard')
    .then(r => r.json())
    .then(data => {
        // Populate table with data
    });
```

Or in PHP (MohaaPlayers.php):
```php
$response = file_get_contents('http://localhost:8080/api/leaderboard');
$data = json_decode($response, true);
$context['leaderboard'] = $data;
```

---

## File Reference

### Local Development Paths

| Path | Description |
|------|-------------|
| `smf-mohaa/Sources/` | All PHP source files |
| `smf-mohaa/Themes/default/` | All template files |
| `smf-mohaa/Themes/default/languages/` | Language files |
| `smf/docker-compose.yml` | Docker services |
| `smf/entrypoint.sh` | Creates symlinks on startup |
| `global/` | Game tracker scripts |
| `cmd/api/` | Go API entry point |
| `internal/` | Go API handlers/logic |

### Container Paths (symlinked from /mohaa)

| Path | Description |
|------|-------------|
| `/var/www/html/` | SMF root directory |
| `/var/www/html/Sources/` | PHP source files (symlinked) |
| `/var/www/html/Themes/default/` | Template files (symlinked) |
| `/mohaa/` | Mount point for smf-mohaa/ |
| `/var/log/apache2/error.log` | Apache error log |

---

## Next Session Checklist

When resuming development:

1. **Start containers:**
   ```bash
   cd /home/elgan/.local/share/openmohaa/main/mohaa-stats-api/smf
   docker-compose up -d
   ```

2. **Verify running:**
   ```bash
   docker ps | grep smf
   ```

3. **Check for errors:**
   ```bash
   docker exec smf-smf-1 tail -n 50 /var/log/apache2/error.log
   ```

4. **Open browser:** http://localhost:8888

5. **Test pages:**
   - `?action=mohaadashboard`
   - `?action=mohaaleaderboard`
   - `?action=mohaamatches`
   - `?action=mohaamaps`
   - `?action=mohaaplayer`
   - `?action=mohaaservers`
   - `?action=mohaaachievements`
   - `?action=mohaatournaments`

6. **Check TASK.md** for current status and priorities
