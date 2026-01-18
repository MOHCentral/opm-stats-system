This is a comprehensive, granular breakdown of every conceivable statistic we can derive from the OpenMOHAA architecture.

Because you are using **ClickHouse** with a **Backend-Authoritative** model, we are not limited by pre-baked summaries. We can query the raw atomic data (billions of rows) to generate specific, high-fidelity metrics.

To achieve this, your `register_event` hooks in the Morpheus/Scripting engine must capture: `attacker`, `victim`, `weapon`, `damage_amount`, `hit_location` (head/torso/legs), `attacker_pos` (x,y,z), `victim_pos` (x,y,z), `attacker_stance`, `victim_stance`, and `timestamp`.

Here is the master list for the ultimate competitive dashboard.

---

### 1. The Soldier Profile (Individual Player Performance)

*Focus: Determining individual skill, lethality, and consistency.*

| Statistic Name | Definition/Formula | UI/Visualisation Style |
| --- | --- | --- |
| **The Executioner Rating** | Headshot percentage across all weapons. | **Gauge Chart** (0-100%) with colour coding (Red=Elite). |
| **Surgical Precision** | Average bullets fired to secure a kill (requires fire event hooking). | **Bar Chart** comparing player vs. server average. |
| **The Wall-Banger** | Kills achieved through geometry (penetration kills). | **Icon Count** next to weapon stats. |
| **Grounded Threat** | Kills secured while fully prone. | **Stat Card** ("Prone Lethality"). |
| **The Acrobat** | Kills secured while in mid-air (jumping). | **Stat Card**. |
| **Lean Efficiency** | Kills secured while leaning (Q/E) vs. standing open. | **Pie Chart** (Stance distribution: Left, Right, Centre). |
| **Basher Rank** | Kills secured using weapon bash/melee. | **Trophy Icon** with count. |
| **Clutch Factor (1vX)** | Win % when left alone against >= 2 enemies. | **Highlight Video Reel** link or Sparkline graph. |
| **Trade Efficiency** | How often the player kills the enemy who just killed a teammate (within 3s). | **Percentage** ("Avenging Angel"). |
| **First Blood Rate** | % of rounds where this player gets the first kill. | **Trend Line** over last 10 matches. |
| **Survivalist Rating** | Kills secured while under 15 HP. | **Stat Card** ("Dead Man Walking"). |
| **Nemesis Identification** | The specific enemy player who has killed this player the most. | **"Wanted Poster"** style UI with that enemy's avatar. |
| **Victim Identification** | The specific enemy player this player kills the most. | **"Dominated"** list. |
| **Aggression Index** | Average movement velocity combined with forward vector during kills. | **Speedometer** graphic. |
| **Cowardice Index** | Time spent stationary in spawn or low-traffic zones while team is dead. | **Heatmap** (Cold zones). |
| **Resource Drain** | Ammo packs and Health packs consumed per kill. | **Ratio** (Cost per Kill). |

### 2. Ballistics & Arsenal (Weapon Specifics)

*Focus: Mastery of specific tools of war.*

| Statistic Name | Definition/Formula | UI/Visualisation Style |
| --- | --- | --- |
| **The Thompson Specialist** | Efficiency rating purely with the Thompson. | **Weapon Icon** with star rating. |
| **Kar98k Snap-Speed** | Average time between spotting an enemy (raycast check) and killing them. | **Time display** (ms). |
| **Grenade Geometry** | Average damage dealt per grenade thrown. | **Bar Chart**. |
| **Longshot King** | Average distance of kills (in in-game units/metres). | **Histogram** of kill distances. |
| **Close Quarters Combat (CQC)** | Win rate in engagements < 5 metres. | **Percentage**. |
| **Sniper Duel Win %** | Win rate when both attacker and victim are using sniper rifles. | **Versus Bar** (Player vs Enemy). |
| **Weapon Versatility** | Standard deviation of kills across all available weapons (Low = Specialist, High = Generalist). | **Spider/Radar Chart**. |
| **Reload Greed** | Deaths occurred whilst reloading. | **Count** (The "Embarrassment" stat). |
| **Pistol Whip** | Kills with secondary weapon after primary runs dry. | **Stat Card**. |

### 3. Spatial & Tactical Analysis (Map & Server)

*Focus: Controlling the battlefield.*

| Statistic Name | Definition/Formula | UI/Visualisation Style |
| --- | --- | --- |
| **The Killbox** | The specific X,Y,Z radius where the most kills occur on the server. | **3D Heatmap Overlay** on map image. |
| **The Death Trap** | Location where players die most frequently without returning fire. | **3D Heatmap** (Red Zones). |
| **Spawn Trap Efficiency** | Kills secured within 5 seconds of enemy spawn (requires spawn logic). | **Heatmap** showing spawn proximity. |
| **Lane Dominance** | Kills secured in the "Middle" vs "Flanks" of a map. | **Map overlay** with arrows. |
| **Verticality Advantage** | Kills secured from a higher Z-axis than the victim. | **Bar Chart** (High Ground vs Low Ground). |
| **Map Mastery** | Win rate broken down by specific map (e.g., V2 Rocket vs. Stalingrad). | **Grid** of maps with win percentages. |
| **Objective Hold Time** | Time spent within the capture radius of a flag/bomb site. | **Timeline**. |
| **Choke Point Control** | Kills registered in doorways or hallways. | **Map Overlay**. |

### 4. Round & Match Dynamics (The Narrative)

*Focus: Momentum and psychological warfare.*

| Statistic Name | Definition/Formula | UI/Visualisation Style |
| --- | --- | --- |
| **Momentum Shift** | The round where the losing team started a comeback streak. | **Line Graph** showing round win probability shifting. |
| **Steamroll Index** | Matches won with a score difference > 7 rounds. | **Count**. |
| **The Carry** | % of total team damage output provided by a single player. | **Donut Chart** (Player vs Rest of Team). |
| **Economy of Force** | Rounds won without losing a single player (Flawless Rounds). | **Trophy List**. |
| **The Throw** | Rounds lost where the team had a man-advantage (e.g., 4v2 loss). | **"Shame" Counter**. |
| **Overtime Performance** | Win rate in the final deciding round (e.g., 9-9 tiebreaker). | **Stat Card**. |

### 5. Tournament & Competitive Hierarchy

*Focus: Ranking, brackets, and elite status.*

| Statistic Name | Definition/Formula | UI/Visualisation Style |
| --- | --- | --- |
| **True Elo Rating** | Mathematical skill rating derived from win/loss + opponents' rank. | **Line Graph** tracking rating over time. |
| **Bracket Destroyer** | % of matches won in the Upper Bracket vs Lower Bracket. | **Split Bar Chart**. |
| **Consistency Score** | Variance in performance between Group Stage and Grand Finals. | **Sparkline**. |
| **Team Synergy** | Win rate when Player A and Player B are on the same team. | **Network Graph** showing strong links. |
| **Map Veto Success** | Win rate on maps picked by the team vs maps picked by the opponent. | **Comparison Bar**. |
| **Dynasty Score** | Number of consecutive tournament wins. | **Badge/Medal**. |

---

### Implementation: The UI Strategy

For the "Best Frontend UI," we utilise the **HTMX + Go Templates** architecture defined in your spec. Avoid heavy JS frameworks.

**The "War Room" Dashboard Concept:**

1. **Header:** Player Name, Current Elo, Clan Tag (e.g., `[UK]Smith`).
2. **The Radar (Spider Chart):** A 5-point axis comparing:
* Aim (Accuracy/Headshots)
* Movement (Velocity/Air Kills)
* Tactics (Flank Kills/Objective Time)
* Survival (Deaths/Damage Taken)
* Clutch (1vX wins)


3. **The Heatmap (Centre Stage):** A high-res top-down render of the favourite map. Using ClickHouse data, render a density layer of red dots (kills) and blue dots (deaths).
4. **The Feed (Right Rail):** A "Ticker" style list of recent achievements.
* *"Jan 17: Achieved 15 Headshots in a single round on Stalingrad."*
* *"Jan 16: Broke personal record for Longest Kill (145m)."*



### Required Scripting Hooks (Morpheus/OpenMOHAA)

To make this possible, you need to ensure `libcod` or your engine modification exposes these events to your "Sidecar" logger:

```c
// Pseudo-code for scripting events
// YOU MUST LOG THESE TO DISK FOR THE SIDECAR TO READ

// When a player takes damage (for ADR and detailed hit stats)
register_event("damage", "player_damage_handler");
// Handler should log: timestamp, attacker_id, victim_id, damage_amount, weapon_id, hit_location_id (head/body/legs)

// When a weapon is fired (to calculate accuracy: shots fired vs shots hit)
register_event("weapon_fire", "weapon_fire_handler");

// Player state changes (to detect crouch/lean/jump)
// This usually requires a loop checking player stance bits, as there isn't always a direct event.
// Log state snapshot every 1 second or on Kill event.

```

### Next Step

Would you like me to generate the **ClickHouse SQL queries** required to calculate the "Heatmap" or "Clutch Factor" metrics from your `raw_events` table?