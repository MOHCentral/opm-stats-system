# 🎨 Event Visualization Strategy
## ApexCharts Configuration for All 92 Events

> **Design Philosophy**: Every number is a doorway to deeper insight.

---

## 📊 Visualization Type Matrix

| Chart Type | Use Case | Events | Complexity |
|------------|----------|--------|------------|
| **Line Chart** | Trends over time | Kill/death trends, accuracy progression | Easy |
| **Area Chart** | Cumulative trends | Total damage, distance traveled | Easy |
| **Bar Chart** | Category comparisons | Weapon usage, map performance | Easy |
| **Column Chart** | Time-series categories | Kills per match, hourly performance | Easy |
| **Pie/Donut Chart** | Composition | Stance distribution, kill type breakdown | Easy |
| **Radar/Spider Chart** | Multi-axis comparison | Player DNA, weapon mastery across metrics | Medium |
| **Heatmap** | Spatial density | Kill/death locations, hit location body map | Medium |
| **Scatter Plot** | Correlation analysis | Accuracy vs K/D, distance vs kills | Medium |
| **Histogram** | Distribution | Kill distance ranges, damage taken | Medium |
| **Sankey Diagram** | Flow/transitions | Weapon switching, stance transitions | Hard |
| **Radial Bar** | Circular categories | 24-hour performance clock | Medium |
| **Timeline** | Event sequences | Match events, reload cycles | Medium |
| **Gauge** | Single value with context | Current accuracy, bot farming % | Easy |
| **Treemap** | Hierarchical composition | Map→Weapon→Kills breakdown | Hard |
| **Bubble Chart** | 3-axis comparison | Kills (Y) vs Deaths (X), bubble = Headshots | Medium |

---

## 🎯 Event-to-Visualization Mapping

### Combat Events (23)

#### Core Combat
| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `kill` | Line (kills/time) | Heatmap (map positions) | Click → Kill breakdown | Player Profile |
| `death` | Line (deaths/time) | Heatmap (death positions) | Click → Death analysis | Player Profile |
| `damage` | Area (cumulative) | Bar (damage by weapon) | Click → Damage breakdown | Player Profile |
| `player_pain` | Body heatmap | Histogram (damage distribution) | Click → Hit location detail | Player Profile |
| `headshot` | Gauge (HS %) | Bar (headshots per weapon) | Click → Weapon HS stats | Weapon Stats |

#### Special Kills
| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `player_bash` | Counter badge | Timeline (bash events) | Click → Bash montage | Player Profile |
| `player_roadkill` | Counter badge | Heatmap (vehicle kill locations) | Click → Vehicle stats | Vehicle Stats |
| `player_suicide` | Counter (with emoji 💀) | Pie (suicide causes) | Click → Shame wall | Player Profile |
| `player_crushed` | Counter badge | Heatmap (crush locations) | Click → Environmental kills | Map Stats |
| `player_telefragged` | Counter (rare!) | Timeline (telefrag events) | Click → Highlight reel | Player Profile |
| `player_teamkill` | Counter (⚠️ red) | Timeline (TK events) | Click → Toxicity report | Player/Team Profile |

#### Weapon Events
| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `weapon_fire` | Counter | Line (fire rate/time) | Click → Shot timeline | Weapon Details |
| `weapon_hit` | Counter | Scatter (hit position) | Click → Hit heatmap | Weapon Details |
| `weapon_change` | Sankey (weapon flow) | Bar (swap frequency) | Click → Weapon usage | Player Profile |
| `weapon_reload` | Timeline (reload events) | Histogram (reload frequency) | Click → Reload analysis | Weapon Details |
| `weapon_reload_done` | Gauge (completion %) | Histogram (reload time) | Click → Efficiency stats | Weapon Details |
| `weapon_ready` | Timeline | - | - | Weapon Details |
| `weapon_no_ammo` | Counter badge (🚫) | Bar (per weapon) | Click → Ammo mgmt | Player Profile |
| `weapon_holster` | Timeline | - | - | Weapon Details |
| `weapon_raise` | Timeline | - | - | Weapon Details |
| `weapon_drop` | Counter | Timeline | Click → Drop events | Player Profile |

#### Grenades
| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `grenade_throw` | Counter | Heatmap (throw locations) | Click → Grenade analysis | Player Profile |
| `grenade_explode` | Scatter (throw→explode) | Heatmap (explosion locations) | Click → Kill radius | Map Details |

### Movement Events (10)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `jump` | Counter | Timeline (jump events) | Click → Jump frequency | Player Profile |
| `land` | Histogram (fall height) | Timeline | Click → Fall damage | Player Profile |
| `crouch` | Pie (stance %) | Timeline (crouch duration) | Click → Tactical analysis | Player Profile |
| `prone` | Pie (stance %) | Timeline (prone duration) | Click → Sniper analysis | Player Profile |
| `player_stand` | Pie (stance %) | Sankey (stance transitions) | Click → Movement pattern | Player Profile |
| `player_spawn` | Timeline | Heatmap (spawn points) | Click → Spawn analysis | Map Details |
| `player_respawn` | Counter | Line (respawns/time) | Click → Death frequency | Player Profile |
| `distance` | Area (cumulative km) | Bar (walk/sprint/swim/drive) | Click → Distance breakdown | Player Profile |
| `ladder_mount` | Counter | Timeline | Click → Vertical mobility | Map Details |
| `ladder_dismount` | Counter (paired with mount) | - | - | Map Details |

### Interaction Events (6)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `player_use` | Counter | Timeline | Click → Interaction log | Player Profile |
| `player_use_object_start` | Timeline | - | Click → Objective detail | Objective Stats |
| `player_use_object_finish` | Histogram (completion time) | Bar (objective completions) | Click → Objective breakdown | Objective Stats |
| `player_spectate` | Counter (time) | Timeline | - | Server Dashboard |
| `player_freeze` | Counter | Timeline | - | Admin Dashboard |
| `player_say` | Word cloud | Timeline (chat frequency) | Click → Chat log | Player Profile |

### Item Events (5)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `item_pickup` | Counter | Bar (item types) | Click → Item breakdown | Player Profile |
| `item_drop` | Counter | Timeline | - | Player Profile |
| `item_respawn` | Heatmap (item locations) | Timeline (respawn timing) | Click → Item timing | Map Details |
| `health_pickup` | Counter | Line (HP over time) | Click → Health management | Player Profile |
| `ammo_pickup` | Counter | Bar (ammo by type) | Click → Ammo management | Player Profile |

### Vehicle & Turret Events (6)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `vehicle_enter` | Counter (time in vehicle) | Timeline | Click → Vehicle usage | Vehicle Stats |
| `vehicle_exit` | Counter (paired) | - | - | Vehicle Stats |
| `vehicle_death` | Counter | Timeline | Click → Vehicle destruction | Vehicle Stats |
| `vehicle_collision` | Counter (crash rate) | Heatmap (collision locations) | Click → Crash analysis | Vehicle Stats |
| `turret_enter` | Counter (time on turret) | Timeline | Click → Turret usage | Turret Stats |
| `turret_exit` | Counter (paired) | - | - | Turret Stats |

### Server Lifecycle Events (5)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `server_init` | Timeline | - | - | Server Dashboard |
| `server_start` | Timeline | - | - | Server Dashboard |
| `server_shutdown` | Timeline | - | - | Server Dashboard |
| `server_spawned` | Timeline | Histogram (server uptime) | Click → Uptime report | Server Dashboard |
| `server_console_command` | Timeline | Bar (command frequency) | Click → Command log | Admin Dashboard |
| `heartbeat` | Line (player count) | Gauge (current players) | Click → Server health | Server Dashboard |

### Map Lifecycle Events (4)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `map_load_start` | Timeline | - | - | Server Dashboard |
| `map_load_end` | Histogram (load time) | Bar (load time per map) | Click → Load analysis | Server Dashboard |
| `map_change_start` | Timeline | - | - | Server Dashboard |
| `map_restart` | Counter | Timeline | Click → Restart frequency | Map Details |

### Game Flow Events (11)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `game_init` | Timeline | - | - | Match Details |
| `game_start` | Timeline | - | - | Match Details |
| `game_end` | Timeline | Histogram (match duration) | Click → Match summary | Match Details |
| `match_start` | Timeline | - | - | Match Details |
| `match_end` | Histogram (match duration) | Bar (win/loss) | Click → Match breakdown | Match Details |
| `round_start` | Timeline | - | - | Match Details |
| `round_end` | Timeline | - | - | Match Details |
| `warmup_start` | Timeline | - | - | Match Details |
| `warmup_end` | Timeline | Bar (warmup duration) | Click → Warmup stats | Match Details |
| `intermission_start` | Timeline | - | - | Match Details |
| `objective_update` | Timeline (status) | Progress bar | Click → Objective detail | Objective Stats |

### Team & Vote Events (5)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `team_join` | Area (team balance) | Timeline | Click → Team history | Team Stats |
| `team_change` | Counter (loyalty score) | Sankey (team flow) | Click → Switch history | Player Profile |
| `vote_start` | Timeline | Counter | Click → Vote detail | Server Dashboard |
| `vote_passed` | Pie (passed vs failed) | Bar (vote types) | Click → Vote history | Server Dashboard |
| `vote_failed` | Pie (paired with passed) | Bar (failure reasons) | Click → Vote analysis | Server Dashboard |

### Client/Session Events (5)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `client_connect` | Timeline | Line (player count) | - | Server Dashboard |
| `client_disconnect` | Timeline | Line (player count) | - | Server Dashboard |
| `client_begin` | Timeline | - | - | Server Dashboard |
| `client_userinfo_changed` | Timeline | Counter (name changes) | Click → Identity changes | Player Profile |
| `player_inactivity_drop` | Counter (⚠️) | Timeline | Click → AFK report | Server Dashboard |

### World Events (3)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `door_open` | Heatmap (door locations) | Counter | Click → Door camper stats | Map Details |
| `door_close` | Timeline (paired) | - | - | Map Details |
| `explosion` | Heatmap (explosion density) | Timeline | Click → Explosion detail | Map Details |

### AI/Actor/Bot Events (7)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `actor_spawn` | Timeline | Counter | - | Campaign Stats |
| `actor_killed` | Counter | Bar (actor types) | Click → AI kills | Campaign Stats |
| `bot_spawn` | Timeline | Counter | - | Bot Stats |
| `bot_killed` | Counter | Gauge (PvE %) | Click → Bot farming | Player Profile |
| `bot_roam` | State diagram | Timeline | Click → Bot behavior | Bot Stats |
| `bot_curious` | State diagram | Timeline | - | Bot Stats |
| `bot_attack` | State diagram | Timeline | - | Bot Stats |

### Objective Events (2)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `objective_update` | Progress bar | Timeline | Click → Objective history | Objective Stats |
| `objective_capture` | Counter | Bar (captures by team) | Click → Capture detail | Objective Stats |

### Score & Admin Events (2)

| Event | Primary Chart | Secondary Chart | Interactive Element | Page |
|-------|--------------|-----------------|---------------------|------|
| `score_change` | Line (score progression) | Bar (score by type) | Click → Score breakdown | Player Profile |
| `teamkill_kick` | Counter (⚠️ red) | Timeline | Click → Toxicity report | Admin Dashboard |

---

## 🛠️ ApexCharts Configuration Templates

### Template 1: Kill/Death Line Chart
```javascript
{
  chart: {
    type: 'line',
    height: 350,
    animations: { enabled: true, speed: 800 },
    toolbar: { show: true },
    zoom: { enabled: true }
  },
  series: [
    { name: 'Kills', data: killsData },
    { name: 'Deaths', data: deathsData }
  ],
  stroke: { curve: 'smooth', width: 3 },
  colors: ['#00E396', '#FF4560'],
  xaxis: {
    type: 'datetime',
    title: { text: 'Match Timeline' }
  },
  yaxis: {
    title: { text: 'Count' }
  },
  tooltip: {
    x: { format: 'HH:mm:ss' },
    shared: true
  },
  legend: { position: 'top' }
}
```

### Template 2: Stance Distribution Pie Chart
```javascript
{
  chart: {
    type: 'donut',
    height: 300
  },
  series: [pronePercent, crouchPercent, standPercent],
  labels: ['Prone', 'Crouch', 'Standing'],
  colors: ['#775DD0', '#FEB019', '#00E396'],
  dataLabels: {
    enabled: true,
    formatter: (val) => val.toFixed(1) + '%'
  },
  legend: {
    position: 'bottom'
  },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Stance',
            formatter: () => 'Distribution'
          }
        }
      }
    }
  }
}
```

### Template 3: Hit Location Body Heatmap (Custom Canvas)
```javascript
// Custom canvas implementation
const drawBodyHeatmap = (canvasId, hitData) => {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext('2d');
  
  // Draw body silhouette
  const bodyImage = new Image();
  bodyImage.src = '/static/body_template.png';
  bodyImage.onload = () => {
    ctx.drawImage(bodyImage, 0, 0, canvas.width, canvas.height);
    
    // Overlay hit locations with heat gradient
    hitData.forEach(hit => {
      const intensity = hit.count / maxHits;
      const gradient = ctx.createRadialGradient(
        hit.x, hit.y, 0,
        hit.x, hit.y, 20
      );
      gradient.addColorStop(0, `rgba(255, 0, 0, ${intensity})`);
      gradient.addColorStop(1, 'rgba(255, 0, 0, 0)');
      
      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(hit.x, hit.y, 20, 0, Math.PI * 2);
      ctx.fill();
    });
  };
};
```

### Template 4: Weapon Swap Sankey Diagram (D3.js)
```javascript
// Requires D3.js and d3-sankey
const sankeyData = {
  nodes: [
    { name: 'M1 Garand' },
    { name: 'Kar98k' },
    { name: 'Springfield' },
    { name: 'Colt .45' }
  ],
  links: [
    { source: 0, target: 1, value: 25 }, // M1 → Kar98k (25 swaps)
    { source: 0, target: 3, value: 15 }, // M1 → Colt .45
    { source: 1, target: 3, value: 30 }  // Kar98k → Colt .45
  ]
};

const sankey = d3.sankey()
  .nodeWidth(15)
  .nodePadding(10)
  .extent([[1, 1], [width - 1, height - 6]]);

const { nodes, links } = sankey(sankeyData);
// Render nodes and links...
```

### Template 5: 24-Hour Performance Radial Bar
```javascript
{
  chart: {
    type: 'radialBar',
    height: 350
  },
  series: hourlyKDRatios, // 24-element array [0-23]
  labels: ['00:00', '01:00', '02:00', ..., '23:00'],
  plotOptions: {
    radialBar: {
      track: {
        background: '#e7e7e7',
        strokeWidth: '97%'
      },
      dataLabels: {
        name: { fontSize: '14px' },
        value: {
          fontSize: '16px',
          formatter: (val) => `K/D: ${val.toFixed(2)}`
        }
      }
    }
  },
  colors: ['#1ab7ea', '#0084ff', '#39539E', '#0077B5']
}
```

### Template 6: Map Position Heatmap
```javascript
// Using Canvas API overlay on map image
const drawMapHeatmap = (canvasId, mapImage, eventData) => {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext('2d');
  
  // Draw map background
  const map = new Image();
  map.src = mapImage;
  map.onload = () => {
    ctx.drawImage(map, 0, 0, canvas.width, canvas.height);
    
    // Create heatmap layer
    const heatmapInstance = h337.create({
      container: canvas.parentElement,
      radius: 25,
      maxOpacity: 0.6,
      minOpacity: 0,
      blur: 0.9,
      gradient: {
        0.0: 'blue',
        0.5: 'yellow',
        1.0: 'red'
      }
    });
    
    // Transform game coords to canvas coords
    const points = eventData.map(evt => ({
      x: scaleX(evt.pos_x),
      y: scaleY(evt.pos_y),
      value: evt.count
    }));
    
    heatmapInstance.setData({
      max: Math.max(...points.map(p => p.value)),
      data: points
    });
  };
};
```

### Template 7: Reload Timeline (Events)
```javascript
{
  chart: {
    type: 'rangeBar',
    height: 250
  },
  series: [{
    name: 'Reload',
    data: reloadEvents.map(evt => ({
      x: evt.weapon,
      y: [evt.startTime, evt.endTime]
    }))
  }],
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true,
      dataLabels: { hideOverflowingLabels: false }
    }
  },
  xaxis: {
    type: 'datetime',
    labels: { format: 'HH:mm:ss' }
  },
  tooltip: {
    custom: ({ dataPointIndex }) => {
      const evt = reloadEvents[dataPointIndex];
      return `<div class="tooltip">
        <strong>${evt.weapon}</strong><br>
        Duration: ${(evt.endTime - evt.startTime).toFixed(2)}s
      </div>`;
    }
  }
}
```

---

## 🎨 SMF Integration Strategy

### Hybrid Design Pattern
Combine SMF's native container styles with rich ApexCharts visualizations.

```php
<!-- Example: Player Profile Stats Card -->
<div class="windowbg">
    <div class="cat_bar">
        <h3 class="catbg">Combat Performance</h3>
    </div>
    <div class="roundframe">
        <!-- Traditional SMF Table for quick scan -->
        <table class="table_grid">
            <tr>
                <td><strong>K/D Ratio:</strong></td>
                <td class="clickable-stat" data-chart="kd-trend">
                    <span class="stat-value">1.85</span>
                    <i class="fas fa-chart-line"></i>
                </td>
            </tr>
            <tr>
                <td><strong>Accuracy:</strong></td>
                <td class="clickable-stat" data-chart="accuracy-trend">
                    <span class="stat-value">45.2%</span>
                    <i class="fas fa-chart-area"></i>
                </td>
            </tr>
        </table>
        
        <!-- Expandable Chart Container (hidden by default) -->
        <div id="kd-trend-chart" class="chart-container" style="display:none;">
            <div id="kd-apex-chart"></div>
        </div>
        
        <div id="accuracy-trend-chart" class="chart-container" style="display:none;">
            <div id="accuracy-apex-chart"></div>
        </div>
    </div>
</div>

<script>
// Click-to-expand charts
document.querySelectorAll('.clickable-stat').forEach(el => {
    el.addEventListener('click', function() {
        const chartId = this.dataset.chart + '-chart';
        const container = document.getElementById(chartId);
        
        if (container.style.display === 'none') {
            container.style.display = 'block';
            loadChart(this.dataset.chart); // Lazy load chart
        } else {
            container.style.display = 'none';
        }
    });
});
</script>
```

---

## 🚀 Implementation Priority

### Phase 1: Essential Charts (This Sprint)
1. ✅ K/D Line Chart
2. ✅ Accuracy Gauge
3. ✅ Weapon Bar Chart
4. [ ] Stance Pie Chart
5. [ ] Kill Distance Histogram

### Phase 2: Interactive Charts (Sprint 2)
1. [ ] Hit Location Body Heatmap
2. [ ] Map Position Heatmap
3. [ ] Weapon Swap Sankey
4. [ ] 24-Hour Radial Bar

### Phase 3: Advanced Visualizations (Sprint 3)
1. [ ] Player DNA Radar Chart
2. [ ] Reload Timeline
3. [ ] Bot Behavior State Diagram
4. [ ] Vote History Timeline

### Phase 4: Creative Charts (Sprint 4)
1. [ ] 3D Map Heatmap (Three.js)
2. [ ] Real-time Match Viewer
3. [ ] Comparative Player Overlay
4. [ ] Tournament Bracket Visualization

---

## 📝 Best Practices

1. **Lazy Loading**: Don't load charts until user clicks "expand" or navigates to page
2. **Responsive**: All charts must work on mobile (use `responsive` option)
3. **Theming**: Match SMF color palette (`#135294` for primary, `#ff6600` for accent)
4. **Performance**: For heatmaps, sample data if > 10,000 points
5. **Accessibility**: Provide text alternative for screen readers
6. **Tooltips**: Rich tooltips with drill-down hints ("Click for details")

**Confidence**: 97% (Need to test some advanced D3.js integrations)
