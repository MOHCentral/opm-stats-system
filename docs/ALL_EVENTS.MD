It's a good start but the dashboard and leaderboard are still too basic. I updated the implamentation plan so far with comments!

we are still being too limited on events. We should have a table and a visual for every stat/event

love what we already have. lets build on it and ehance it. 
Both our dashboard (war room) and our leaderboard

We should be building atats analisys for both player and server.

Analyse all our.md files and udnerstand what we want to finally achieve!

1. the war room (dashboard) is still very basic! 
2. The leaderboard pages are broken


We should implament fun ui graphs, leaderboards, widgets, boards, breakdowns for every single stat and add achievments too.
It should be really fun and creative while also being massivly in depth


# OpenMoHAA Event Subscribe Reference

This document lists ONLY the events that can be used with `event_subscribe`.

**Total Subscribable Events: 67**

---

## ScriptDelegate Events (Stats/Hooks)

These are the subscribable events for stat tracking via `event_subscribe`.

### Combat Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `player_kill` | Player killed another player | attacker, attacker, victim, inflictor, location, mod |
| `player_death` | Player died | player, inflictor |
| `player_damage` | Player took damage | player, attacker, damage, mod |
| `player_pain` | Player pain with hit location | player, attacker, damage, mod, location |
| `player_headshot` | Headshot kill | attacker, victim, weapon |
| `player_suicide` | Player suicide | player |
| `player_crushed` | Player crushed | player, attacker |
| `player_telefragged` | Player telefragged | player, attacker |
| `player_roadkill` | Vehicle kill | attacker, victim |
| `player_bash` | Melee bash kill | attacker, victim |
| `player_teamkill` | Team kill | killer, victim |
| `weapon_fire` | Weapon fired | owner, weapon_name, ammo_left |
| `weapon_hit` | Weapon hit target | owner, target, location/type |
| `weapon_reload` | Weapon reloading | owner, weapon_name |
| `weapon_reload_done` | Reload complete | owner, weapon_name |
| `weapon_change` | Weapon switched | owner, old_weapon, new_weapon, client |
| `weapon_ready` | Weapon ready | owner, weapon_name |
| `weapon_no_ammo` | Out of ammo | owner, weapon_name |
| `weapon_holster` | Weapon holstered | owner, weapon_name |
| `weapon_raise` | Weapon raised | owner, weapon_name |
| `weapon_drop` | Weapon dropped | owner, weapon |
| `grenade_throw` | Grenade thrown | owner, projectile |
| `grenade_explode` | Grenade exploded | owner, projectile |

### Movement Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `player_spawn` | Player spawned | player |
| `player_jump` | Player jumped | player |
| `player_land` | Player landed | player, height |
| `player_crouch` | Player crouched | player |
| `player_prone` | Player went prone | player |
| `player_distance` | Distance traveled | player, walked, sprinted, swam, driven |
| `ladder_mount` | Mounted ladder | player, ladder |
| `ladder_dismount` | Dismounted ladder | player, ladder |

### Interaction Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `player_use` | Player used something | player, entity |
| `player_use_object_start` | Started using object | player, object |
| `player_use_object_finish` | Finished using object | player, object |
| `player_spectate` | Became spectator | player |
| `player_freeze` | Player frozen/unfrozen | player, frozen_state |
| `player_say` | Chat message | player, message |

### Item Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `item_pickup` | Item picked up | player, item_name, amount |
| `item_drop` | Item dropped | player, item_name |
| `health_pickup` | Health picked up | player, amount |
| `ammo_pickup` | Ammo picked up | player, item_name, amount |
| `armor_pickup` | Armor picked up | (via item_pickup) |

### Vehicle/Turret Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `vehicle_enter` | Entered vehicle | player, vehicle |
| `vehicle_exit` | Exited vehicle | player, vehicle |
| `vehicle_death` | Vehicle destroyed | vehicle, attacker |
| `vehicle_collision` | Vehicle collision | vehicle, other |
| `turret_enter` | Entered turret | player, turret |
| `turret_exit` | Exited turret | player, turret |

### Game Flow Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `game_init` | Game initialized | gametype |
| `game_start` | Game started | (none) |
| `game_end` | Game ended | (none) |
| `round_start` | Round started | (none) |
| `round_end` | Round ended | (none) |
| `team_win` | Team won | teamnum |
| `team_join` | Player changed team | player, old_team, new_team |
| `objective_update` | Objective changed | index, status |

### World Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `door_open` | Door opened | door, activator |
| `door_close` | Door closed | door |

### Client Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `client_connect` | Client connected | clientNum |
| `client_begin` | Client began | player |
| `client_userinfo_changed` | Userinfo changed | player |
| `client_disconnect` | Client disconnected | player |

### Bot Events
| Event | Description | Parameters |
|-------|-------------|------------|
| `bot_spawn` | Bot spawned | bot |
| `bot_killed` | Bot killed | bot, attacker |
| `bot_roam` | Bot roaming | bot |
| `bot_curious` | Bot curious | bot |
| `bot_attack` | Bot attacking | bot |

---

Embelish on our atsts pages, dashboard, war room, leaderboards, server stats., server leadboards, server analyss, battle, round, game, 



continue;
 remember
API integration: SMF uses local DB, Go API uses Postgres; they’re disconnected

* ALL OUR EVENTS should be stored in clickhouse? the go api should dump all our events
* All player related things like teams, tournements and tokens should go into a DB stored in SMF local DB  (since they use the player uid from smf , or should do)