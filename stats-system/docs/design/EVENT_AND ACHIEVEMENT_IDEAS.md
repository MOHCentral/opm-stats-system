Here is the definitive UI strategy for every single metric and achievement. We are aiming for a **Command & Control** aesthetic: militaristic, high-contrast, data-dense, and meritocratic.

### **PART 1: THE STATISTICS (OPERATIONAL INTEL)**

#### **A. Lethality & Combat Efficiency (The "Killer" Dashboard)**

*Context: These stats belong on the main "Operator Profile" summary page.*

1. **Total Kills:** **Digital Odometer.** A rolling counter that ticks up visually.
2. **Total Deaths:** **Small Subtext** below kills (muted grey colour) to de-emphasise failure.
3. **Kill/Death Ratio (KDR):** **Speedometer Gauge.** 0-1 is Red, 1-2 is Yellow, 2-5 is Green, 5+ is Burning/Animated.
4. **Average Damage per Round (ADR):** **Bar Chart** comparing Player vs. Server Average.
5. **Kills Per Minute (KPM):** **Sparkline Graph.** A small line graph showing KPM trends over the last 10 matches.
6. **Headshot Percentage (HSP):** **Human Silhouette.** A wireframe body where the head glows brighter red the higher the %.
7. **Torso Kill Percentage:** **Human Silhouette.** The chest area is shaded based on %.
8. **Limb Kill Percentage:** **Human Silhouette.** Legs/Arms shaded (indicates poor aim).
9. **Accuracy:** **Target Reticle Icon.** The closer to 100%, the tighter the crosshair graphic appears.
10. **Nutshots:** **Icon Badge.** A cracked nut icon with a count. Click to see a "Hall of Shame" list of victims.
11. **Helmet Poppers:** **Animation.** A helmet flying off icon that animates on hover.
12. **Gibs:** **Gore Splatter Background.** The more gibs, the bloodier the background behind the stat.
13. **Assists:** **Handshake Icon.** Simple counter.
14. **Trade Kills:** **Link Icon.** A chain link graphic connecting two skulls.
15. **Trade Deaths:** **Broken Link Icon.**
16. **First Bloods:** **Timeline Marker.** A horizontal bar representing a round; a pin shows *when* the kill happened (early vs late).
17. **Final Blows:** **Gavel Icon.**
18. **Solo Kills:** **Lone Wolf Icon.**
19. **Cleanups:** **Broom Icon.**

#### **B. Weapon Mastery (The "Armoury" View)**

*Context: A grid of weapon cards. Clicking a card expands to show details.*

20. **[Weapon] Kills:** **Progress Bar.** Fills up towards the next mastery tier (Bronze -> Silver -> Gold).
21. **[Weapon] Deaths:** **Inverted Bar.** Red bar growing downwards.
22. **[Weapon] Accuracy:** **Scatter Plot.** A visual representation of a spray pattern (tight vs loose).
23. **[Weapon] Headshot Ratio:** **Pie Chart.** Head vs Body vs Limb split for *that specific gun*.
24. **[Weapon] Time Equipped:** **Clock Face.**
25. **[Weapon] Reloads:** **Mag Icon.** Stack of magazines representing count.
26. **[Weapon] Empty Clicks:** **"Jam" Warning.** A blinking yellow hazard light.
27. **[Weapon] Drop Rate:** **Trash Can Icon.**
28. **Grenade Efficiency:** **Blast Radius Circle.** Visualises average damage area (small circle = bad, large circle = good).
29. **Rocket Direct Hits:** **Bullseye.**
30. **Splash Kills:** **Ripple Effect.**
31. **Melee Kills:** **Brass Knuckles.**
32. **Sniper Duels Won:** **Split Screen Image.** Your avatar standing over the enemy avatar.
33. **Sniper Duels Lost:** **Tombstone.**

#### **C. Movement & Positioning (The "Tactical" View)**

*Context: These require spatial visualisation.*

34. **Distance Travelled:** **Map Path.** A line drawn across a globe icon.
35. **Sprint Duration:** **Stamina Bar.** Like in-game HUDs.
36. **Crouch Time:** **Icon Height.** A crouching soldier icon.
37. **Prone Time:** **Icon Height.** A prone soldier icon.
38. **Airtime:** **Altimeter.** A gauge showing "Time off ground".
39. **Jump Count:** **Rabbit Icon.**
40. **Ladder Climbs:** **Vertical Ruler.**
41. **Stationary Time:** **Tent Icon.** (Mocking campers).
42. **Average Velocity:** **Tachometer.**
43. **Stance Transitions:** **Waveform Graph.** Shows frequency of state changes.
44. **Lean Usage:** **Balance Scale.** Tips Left or Right based on preference.
45. **Strafe Distance:** **Horizontal Arrows.**

#### **D. Situational & Contextual (The "Combat Record")**

*Context: Narrative lists and highlights.*

46. **Clutch 1v1 Wins:** **Video Play Button.** Auto-generates a playlist of these moments if replays exist.
47. **Clutch 1v2 Wins:** **Video Play Button.**
48. **Clutch 1v3+ Wins:** **Gold Play Button.** Highlighted prominently.
49. **Backstabs:** **Knife in Back Icon.**
50. **Wallbangs:** **X-Ray Vision.** An icon showing a skeleton through a wall.
51. **Spawn Kills:** **Stopwatch.** Shows time < 5s.
52. **Spawn Deaths:** **Broken Stopwatch.**
53. **Longshots:** **Ruler.** Shows longest distance (e.g., "150m").
54. **Point Blank Kills:** **Fist Icon.**
55. **Reload Kills:** **Luck Clover.**
56. **Flash Kills:** **Sunglasses.**
57. **Low HP Kills:** **Blood Vignette.** A screen border effect on the stat card.
58. **Killstreaks:** **Combo Counter.** Arcade style font (e.g., "15x COMBO").
59. **Multikills:** **Stacking Skulls.** 2 skulls, 3 skulls, 4 skulls piles.
60. **Nemesis:** **Wanted Poster.** Auto-generates a poster with that player's face/name.
61. **Victim:** **Graveyard.** A list of names on tombstones.

#### **E. Objective & Teamplay (The "Mission" View)**

*Context: Blue/Green tactical colours.*

62. **Bomb Plants:** **Bomb Icon.** Stacks of C4.
63. **Bomb Defuses:** **Wire Cutters.**
64. **Bomb Explosions:** **Explosion GIF.** Small animation on hover.
65. **Flag Captures:** **Flag Icon.**
66. **Flag Returns:** **Shield Icon.**
67. **Carrier Kills:** **Crosshair over Flag.**
68. **Objective Time:** **Hourglass.**
69. **Defensive Kills:** **Turret Icon.**
70. **Offensive Kills:** **Sword Icon.**

#### **F. Map & Environmental (The "Geospatial" View)**

71. **Most Played Map:** **Interactive Card.** Hovering shows a mini-heatmap of that map. Clicking opens the Leaderboard for that map *specifically* (ordered by playtime).
72. **Best Map:** **Green Border Card.** Map image with "High Ground" stamp.
73. **Worst Map:** **Red Border Card.** Map image with "No Go Zone" stamp.
74. **Doors Opened:** **Door Icon.** An animated door that opens on hover.
75. **Windows Smashed:** **Shattered Glass Overlay.**
76. **Fall Deaths:** **Parachute with Hole.**
77. **Drownings:** **Anchor.**
78. **Crushed:** **Hydraulic Press.**
79. **Hazard Kills:** **Biohazard Symbol.**

---

### **PART 2: THE ACHIEVEMENTS (THE MEDAL CASE)**

*UI Strategy: A physical "Wooden Cabinet" or "Ribbon Rack" aesthetic. Badges are greyed out until unlocked. When unlocked, they shine/glimmer.*

#### **Tier 1: Basic Training**

* **UI:** Simple, flat metal pins.
* **1-8:** Displayed in a "Rookie Row" at the bottom.

#### **Tier 2: Weapon Specialist**

* **UI:** High-detail weapon renders embossed on silver coins.
* **9-18:** Clicking the coin rotates it 3D to show the date unlocked and exact kill count.

#### **Tier 3: Tactical & Skill**

* **UI:** Gold Medals with ribbons.
* **19-29:** These occupy the centre of the profile. They should have a "lens flare" effect.

#### **Tier 4: Humiliation**

* **UI:** Cartoonish / Graffiti style patches.
* **30-39:** These look like morale patches (Velcro style) to differentiate them from serious medals.
* *Example:* **Grave Dancer** is a tea bag illustration.



#### **Tier 5: Hall of Shame**

* **UI:** "Rusty" or "Broken" medals.
* **40-47:** These are displayed in a dark corner titled "Dishonourable Discharges". They have a cracked glass effect over them.

#### **Tier 6: Map & World**

* **UI:** Postcards or Travel Stamps.
* **48-55:** Styled like passport stamps. "Visited V2 Rocket", "Sewer Rat Certified".

#### **Tier 7: Dedication**

* **UI:** Elaborate, jewelled trophies.
* **56-62:** Large, vertical trophies that stand on the "shelf" of the UI.
* **Founder:** A platinum plaque with the date "2025".



### **Summary of UI Interactions**

1. **The Hover:** Every stat must have a tooltip explaining the formula (e.g., "ADR = Total Dmg / Rounds").
2. **The Click:** Clicking any stat (e.g., "Headshots") instantly opens a **Global Leaderboard** filtered by that specific stat.
* *Click "Nutshots" -> Shows list of players with most nutshots.*


3. **The Comparison:** A "VS" button allows you to select another player and see their silhouette/spider-chart overlaid on yours in a different colour.

VERY IMPORTANT!
Clicking on ANY stat takes you to a table that shows you all of them;
For example in server stats clicking on "most kills" takes you to a table with a list of all the players and their kills on that server.
We should be able to explode and investigate EVERY single stats