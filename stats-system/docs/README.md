# 📚 OpenMOHAA Stats System Documentation

> **MASSIVE Competitive Statistics Infrastructure for Medal of Honor: Allied Assault**

## 📁 Documentation Structure

```
docs/
├── README.md                       # This file - Documentation hub
├── architecture/
│   ├── SYSTEM_ARCHITECTURE.md      # Full system design ✅
│   ├── CLICKHOUSE_QUERIES.md       # 30+ SQL queries for analytics ✅
│   ├── DATA_FLOW.md                # Event ingestion pipeline
│   └── DATABASE_SCHEMA.md          # ClickHouse + PostgreSQL schemas
├── stats/
│   ├── STATS_MASTER.md             # 100,000+ metric taxonomy ✅
│   ├── ADVANCED_ANALYTICS.md       # Micro-telemetry & deep analysis ✅
│   ├── EVENTS.md                   # 30 engine events reference ✅
│   ├── ACHIEVEMENTS.md             # 540+ achievement definitions ✅
│   └── VISUALIZATIONS.md           # UI/UX specifications ✅
├── development/
│   ├── TASK.md                     # Current sprint tracker
│   ├── DEVELOPER_GUIDE.md          # SMF integration guide
│   ├── API_REFERENCE.md            # REST API documentation
│   └── DEBUGGING.md                # Troubleshooting guide
├── smf/
│   ├── PLUGIN_GUIDE.md             # SMF plugin development
│   ├── TEMPLATES.md                # Template reference
│   └── HOOKS.md                    # SMF hooks used
└── ai/
    ├── CLAUDE.md                   # Claude instructions (→ ../CLAUDE.md)
    ├── COPILOT.md                  # Copilot instructions (→ ../.github/copilot-instructions.md)
    └── AGENTS.md                   # Multi-agent rules (→ ../AGENTS.md)
```

### ✅ = Complete | Others = Planned

## 🎯 Project Vision

Build the most comprehensive competitive statistics and tournament infrastructure for OpenMOHAA:

- **100,000+ trackable metrics** derived from 30 atomic engine events
- **1,000+ achievements** across 10 tiers from Bronze to Legend
- **Drill-down everything** - every stat is clickable, explorable, comparable
- **Rich visualizations** - heatmaps, spider charts, momentum graphs, Sankey diagrams
- **Tournament ecosystem** - brackets, teams, Elo ratings, league seasons
- **SMF integration** - seamless forum + stats + community

## 🔢 Stats at a Glance

| Category | Metrics | Description |
|----------|---------|-------------|
| Combat Core | 60+ | Kills, deaths, KDR, damage, accuracy |
| Weapon Stats | 25 per weapon × 20+ weapons = 500+ | Per-weapon mastery metrics |
| Movement | 50+ | Distance, velocity, stance time, jumps |
| Accuracy | 40+ | Headshots, hit regions, precision |
| Session | 30+ | Time played, matches, rounds |
| Clutch | 50+ | 1vX wins, comebacks, momentum |
| Objective | 40+ | Plants, defuses, captures, holds |
| Map-Specific | 100+ per map | Heatmaps, lane control, spawns |
| Combinations | 50,000+ | Cross-dimensional analysis |

## 🏆 Achievement Tiers

| Tier | Name | Color | Example |
|------|------|-------|---------|
| 1 | Bronze | 🟫 | First Kill |
| 2 | Silver | ⬜ | 100 Kills |
| 3 | Gold | 🟨 | 500 Headshots |
| 4 | Platinum | 💎 | 10 Ace Rounds |
| 5 | Diamond | 💠 | Master all weapons |
| 6 | Master | 🟣 | 1,000 Clutch wins |
| 7 | Grandmaster | 🔴 | Win tournament |
| 8 | Champion | 🟠 | Dynasty (3 wins) |
| 9 | Legend | ⚫ | Perfect season |
| 10 | Immortal | 👑 | Community voted |

## 🛠️ Tech Stack

```
┌─────────────────────────────────────────────────────────────────┐
│                    OpenMOHAA Game Servers                       │
│              tracker.scr → HTTP events → API                    │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Go Stats API                              │
│          Worker Pool → ClickHouse + PostgreSQL + Redis          │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                     SMF Forum (PHP)                             │
│      MohaaPlayers.php → Templates → ApexCharts + HTMX          │
└─────────────────────────────────────────────────────────────────┘
```

## 🚀 Quick Start

1. **Start SMF**: `cd mohaa-stats-api/smf && docker-compose up -d`
2. **Start API**: `cd mohaa-stats-api && go run ./cmd/api`
3. **Access Forum**: http://localhost:8888
4. **View Stats**: http://localhost:8888/?action=mohaadashboard

## 📖 Key Documents

### 📊 Statistics & Metrics
- [STATS_MASTER.md](stats/STATS_MASTER.md) - Complete 100,000+ metric taxonomy
- [ADVANCED_ANALYTICS.md](stats/ADVANCED_ANALYTICS.md) - Micro-telemetry, frame-by-frame, predictions
- [EVENTS.md](stats/EVENTS.md) - 30 engine events with parameters
- [ACHIEVEMENTS.md](stats/ACHIEVEMENTS.md) - 540 achievements across 10 tiers

### 🏗️ Architecture & Implementation  
- [SYSTEM_ARCHITECTURE.md](architecture/SYSTEM_ARCHITECTURE.md) - Full system design
- [CLICKHOUSE_QUERIES.md](architecture/CLICKHOUSE_QUERIES.md) - 30+ SQL queries for all analytics
- [VISUALIZATIONS.md](stats/VISUALIZATIONS.md) - UI/UX with 200+ chart examples

### 🤖 AI Assistant Instructions
- [CLAUDE.md](../CLAUDE.md) - Claude AI instructions
- [copilot-instructions.md](../.github/copilot-instructions.md) - GitHub Copilot instructions
- [AGENTS.md](../AGENTS.md) - Multi-agent workspace rules

---

## 🆕 Recent Additions (Jan 2026)

### ADVANCED_ANALYTICS.md - New!
- **Micro-Event Analytics**: Per-bullet telemetry, frame-by-frame combat analysis
- **Temporal Analytics**: Performance decay, momentum, fatigue curves
- **Spatial Analytics**: 3D engagement geometry, sightline analysis
- **Relational Analytics**: Player vs player matrix, team synergy
- **Predictive Analytics**: Win probability models, player forecasts
- **500,000+ pre-computed aggregations possible**

### CLICKHOUSE_QUERIES.md - New!
- 30+ production-ready SQL queries
- Killstreak detection, clutch win analysis
- Spatial heatmap generation
- Head-to-head breakdowns
- Materialized view definitions
- Query optimization tips

### VISUALIZATIONS.md - New!  
- Complete UI theme specification
- 8 chart types with examples
- 6 page templates with ASCII mockups
- Interactive element designs
- CSS variable reference
- Responsive breakpoints

---

*Last Updated: 2026-01-18*
