#!/usr/bin/env python3
"""
Comprehensive Data Seeder for MOHAA Stats System
Generates realistic game data with ALL event types for complete dashboard testing.
"""

import requests
import random
import json
from datetime import datetime, timedelta
from typing import Dict, List

# Configuration
API_URL = "http://localhost:8080/api/v1/ingest/events"
SERVER_TOKEN = "seeder_test_token_2026"
PLAYER_GUID = "72750883-29ae-4377-85c4-9367f1f89d1a"
PLAYER_NAME = "elgan"
PLAYER_SMF_ID = 1
TOTAL_MATCHES = 100

# Data pools
WEAPONS = [
    "Thompson", "M1 Garand", "Kar98k", "MP40", "STG44", "BAR",
    "Springfield", "Colt .45", "Walther P38", "Shotgun",
    "Panzerschreck", "Bazooka", "Fists", "Kar98k Sniper"
]

MAPS = [
    "dm/mohdm1", "dm/mohdm2", "dm/mohdm3", "dm/mohdm4", "dm/mohdm5",
    "dm/mohdm6", "dm/mohdm7", "obj/obj_team1", "obj/obj_team2",
    "obj/obj_team3", "obj/obj_team4", "lib/stalingrad", "tdm/bridge"
]

SERVERS = ["Main DM", "EU Objective", "US FFA", "Asia TDM", "Pro League"]

OTHER_PLAYERS = [
    "ShadowSniper", "IronWolf", "DeadlyEagle", "SilentKnight", "RapidFire",
    "GhostHunter", "NightStalker", "BulletStorm", "VenomStrike", "ThunderBolt"
]

OTHER_GUIDS = [f"guid-{i:03d}" for i in range(1, 11)]

HIT_LOCS = ["head", "torso", "left_arm", "right_arm", "left_leg", "right_leg", "neck", "pelvis"]
MODS = ["rifle", "smg", "pistol", "sniper", "shotgun", "rocket", "grenade", "bash", "falling"]
ITEMS = ["health_large", "health_small", "ammo_rifle", "ammo_smg", "armor", "helmet", "medkit"]
VEHICLES = ["tank", "jeep", "truck", "halftrack"]

# Stats tracking
events_sent = 0
matches_won = 0

def send_event(event_data: Dict) -> bool:
    """Send a single event to the API"""
    global events_sent
    try:
        response = requests.post(
            API_URL,
            json=event_data,
            headers={
                "Content-Type": "application/json",
                "Authorization": f"Bearer {SERVER_TOKEN}"
            },
            timeout=5
        )
        if response.status_code in [200, 201, 202]:
            events_sent += 1
            return True
        else:
            print(f"❌ Event failed: {response.status_code} - {response.text[:100]}")
            return False
    except Exception as e:
        print(f"❌ Error sending event: {e}")
        return False

def generate_timestamp(days_ago: int, offset_seconds: int = 0) -> float:
    """Generate Unix timestamp (float)"""
    base = datetime.utcnow() - timedelta(days=days_ago)
    ts = base - timedelta(seconds=offset_seconds)
    return ts.timestamp()

def generate_match(match_num: int) -> None:
    """Generate a complete match with all event types"""
    global matches_won
    
    match_id = f"match_{match_num:05d}"
    map_name = random.choice(MAPS)
    server_id = random.choice(SERVERS)
    days_ago = (TOTAL_MATCHES - match_num) // 10  # Spread over ~10 days
    duration = random.randint(600, 2400)  # 10-40 minutes
    
    # Match parameters
    won = random.random() < 0.7  # 70% win rate
    if won:
        matches_won += 1
    
    kills = random.randint(5, 25)
    deaths = random.randint(2, 12)
    shots_fired = kills * 8 + random.randint(20, 80)
    hits = int(shots_fired * random.uniform(0.6, 0.8))
    grenades = random.randint(1, 6)
    jumps = random.randint(10, 40)
    distance_walked = random.randint(300, 1500)
    distance_sprinted = random.randint(200, 1000)
    
    # Connect
    send_event({
        "event_type": "connect",
        "timestamp": generate_timestamp(days_ago, duration),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID
    })
    
    # Spawn
    send_event({
        "event_type": "spawn",
        "timestamp": generate_timestamp(days_ago, duration - 2),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID,
        "team": "allies"
    })
    
    # Generate events throughout match
    for i in range(shots_fired):
        offset = random.randint(0, duration - 10)
        weapon = random.choice(WEAPONS)
        send_event({
            "event_type": "weapon_fire",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "weapon": weapon,
            "ammo_remaining": random.randint(0, 30)
        })
    
    for i in range(hits):
        offset = random.randint(0, duration - 10)
        weapon = random.choice(WEAPONS)
        victim = random.choice(OTHER_PLAYERS)
        victim_guid = random.choice(OTHER_GUIDS)
        hitloc = random.choice(HIT_LOCS)
        send_event({
            "event_type": "weapon_hit",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "attacker_guid": PLAYER_GUID,
            "attacker_name": PLAYER_NAME,
            "attacker_smf_id": PLAYER_SMF_ID,
            "target_id": victim_guid,
            "target_name": victim,
            "weapon": weapon,
            "hit_location": hitloc,
            "damage": random.randint(10, 60)
        })
    
    for i in range(kills):
        offset = random.randint(0, duration - 10)
        weapon = random.choice(WEAPONS)
        victim = random.choice(OTHER_PLAYERS)
        victim_guid = random.choice(OTHER_GUIDS)
        hitloc = random.choice(HIT_LOCS)
        mod = random.choice(MODS)
        
        # Damage before kill
        send_event({
            "event_type": "damage",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "attacker_guid": PLAYER_GUID,
            "attacker_name": PLAYER_NAME,
            "attacker_smf_id": PLAYER_SMF_ID,
            "target_id": victim_guid,
            "target_name": victim,
            "damage": random.randint(50, 120),
            "means_of_death": mod
        })
        
        # Kill event
        send_event({
            "event_type": "kill",
            "timestamp": generate_timestamp(days_ago, duration - offset - 1),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "attacker_guid": PLAYER_GUID,
            "attacker_name": PLAYER_NAME,
            "attacker_smf_id": PLAYER_SMF_ID,
            "target_id": victim_guid,
            "target_name": victim,
            "weapon": weapon,
            "hit_location": hitloc,
            "means_of_death": mod,
            "damage": 100
        })
        
        # Headshot (30% chance)
        if random.random() < 0.3:
            send_event({
                "event_type": "headshot",
                "timestamp": generate_timestamp(days_ago, duration - offset - 1),
                "server_id": server_id,
                "map_name": map_name,
                "match_id": match_id,
                "attacker_guid": PLAYER_GUID,
                "attacker_name": PLAYER_NAME,
                "attacker_smf_id": PLAYER_SMF_ID,
                "target_id": victim_guid,
                "target_name": victim,
                "weapon": weapon
            })
    
    # Deaths
    for i in range(deaths):
        offset = random.randint(0, duration - 10)
        killer = random.choice(OTHER_PLAYERS)
        killer_guid = random.choice(OTHER_GUIDS)
        weapon = random.choice(WEAPONS)
        send_event({
            "event_type": "death",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "attacker_id": killer_guid,
            "attacker_name": killer,
            "weapon": weapon
        })
        
        # Respawn
        send_event({
            "event_type": "spawn",
            "timestamp": generate_timestamp(days_ago, duration - offset - 5),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "team": "allies"
        })
    
    # Grenades
    for i in range(grenades):
        offset = random.randint(0, duration - 10)
        send_event({
            "event_type": "grenade_throw",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "projectile_type": "grenade"
        })
        
        # Grenade kill (40% success)
        if random.random() < 0.4:
            victim = random.choice(OTHER_PLAYERS)
            victim_guid = random.choice(OTHER_GUIDS)
            send_event({
                "event_type": "grenade_kill",
                "timestamp": generate_timestamp(days_ago, duration - offset - 2),
                "server_id": server_id,
                "map_name": map_name,
                "match_id": match_id,
                "attacker_guid": PLAYER_GUID,
                "attacker_name": PLAYER_NAME,
                "attacker_smf_id": PLAYER_SMF_ID,
                "target_id": victim_guid,
                "target_name": victim
            })
    
    # Movement events
    for i in range(jumps):
        offset = random.randint(0, duration - 10)
        send_event({
            "event_type": "jump",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID
        })
    
    # Distance traveled
    send_event({
        "event_type": "distance",
        "timestamp": generate_timestamp(days_ago, 5),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID,
        "walked": distance_walked,
        "sprinted": distance_sprinted,
        "jumped": jumps
    })
    
    # Weapon changes
    for i in range(random.randint(3, 8)):
        offset = random.randint(0, duration - 10)
        old_weapon = random.choice(WEAPONS)
        new_weapon = random.choice(WEAPONS)
        
        send_event({
            "event_type": "weapon_holster",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "weapon": old_weapon
        })
        
        send_event({
            "event_type": "weapon_raise",
            "timestamp": generate_timestamp(days_ago, duration - offset - 1),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "weapon": new_weapon
        })
    
    # Stances
    send_event({
        "event_type": "crouch",
        "timestamp": generate_timestamp(days_ago, duration - random.randint(0, duration)),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID
    })
    
    send_event({
        "event_type": "prone",
        "timestamp": generate_timestamp(days_ago, duration - random.randint(0, duration)),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID
    })
    
    # Vehicle (10% of matches)
    if random.random() < 0.1:
        vehicle = random.choice(VEHICLES)
        offset = random.randint(100, duration - 100)
        send_event({
            "event_type": "vehicle_enter",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "vehicle": vehicle
        })
        
        send_event({
            "event_type": "vehicle_kill",
            "timestamp": generate_timestamp(days_ago, duration - offset - 30),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "attacker_guid": PLAYER_GUID,
            "attacker_name": PLAYER_NAME,
            "attacker_smf_id": PLAYER_SMF_ID,
            "target_id": random.choice(OTHER_GUIDS),
            "target_name": random.choice(OTHER_PLAYERS),
            "vehicle": vehicle
        })
    
    # Item pickups
    for i in range(random.randint(3, 7)):
        offset = random.randint(0, duration - 10)
        item = random.choice(ITEMS)
        send_event({
            "event_type": "item_pickup",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "item_type": item,
            "quantity": 1
        })
    
    # Bot kill (20% of matches)
    if random.random() < 0.2:
        offset = random.randint(0, duration - 10)
        send_event({
            "event_type": "bot_killed",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "attacker_guid": PLAYER_GUID,
            "attacker_name": PLAYER_NAME,
            "attacker_smf_id": PLAYER_SMF_ID,
            "bot_name": f"AI_Soldier_{random.randint(1, 10)}"
        })
    
    # Objective capture (for obj/ maps)
    if map_name.startswith("obj/"):
        for i in range(random.randint(1, 3)):
            offset = random.randint(0, duration - 10)
            send_event({
                "event_type": "objective_capture",
                "timestamp": generate_timestamp(days_ago, duration - offset),
                "server_id": server_id,
                "map_name": map_name,
                "match_id": match_id,
                "player_guid": PLAYER_GUID,
                "player_name": PLAYER_NAME,
                "player_smf_id": PLAYER_SMF_ID,
                "objective_index": random.randint(0, 4)
            })
    
    # Door interactions
    for i in range(random.randint(5, 15)):
        offset = random.randint(0, duration - 10)
        send_event({
            "event_type": "door_open",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID
        })
    
    # Chat
    for i in range(random.randint(2, 5)):
        offset = random.randint(0, duration - 10)
        send_event({
            "event_type": "chat",
            "timestamp": generate_timestamp(days_ago, duration - offset),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID,
            "message": random.choice(["gg", "nice shot", "lol", "wp", "thanks"])
        })
    
    # Match end - Team win event
    if won:
        send_event({
            "event_type": "team_win",
            "timestamp": generate_timestamp(days_ago, 3),
            "server_id": server_id,
            "map_name": map_name,
            "match_id": match_id,
            "team": "allies",
            "player_guid": PLAYER_GUID,
            "player_name": PLAYER_NAME,
            "player_smf_id": PLAYER_SMF_ID
        })
    
    # Spectate
    send_event({
        "event_type": "player_spectate",
        "timestamp": generate_timestamp(days_ago, 1),
        "server_id": server_id,
        "map_name": map_name,
        "match_id": match_id,
        "player_guid": PLAYER_GUID,
        "player_name": PLAYER_NAME,
        "player_smf_id": PLAYER_SMF_ID
    })
    
    if match_num % 10 == 0:
        print(f"   ✓ Match {match_num}/{TOTAL_MATCHES} complete ({events_sent} events, {matches_won} wins)")

def main():
    print(f"🚀 Starting Comprehensive Seeder for {PLAYER_NAME} ({PLAYER_GUID})")
    print(f"   Target: {TOTAL_MATCHES} matches with complete event coverage")
    print()
    
    for i in range(1, TOTAL_MATCHES + 1):
        generate_match(i)
    
    win_rate = (matches_won / TOTAL_MATCHES) * 100
    print()
    print("✅ Seeding Complete!")
    print(f"   📊 Matches: {TOTAL_MATCHES}")
    print(f"   🏆 Wins: {matches_won} ({win_rate:.1f}%)")
    print(f"   ⚡ Events: {events_sent}")
    print()
    print("Refresh your dashboard to see comprehensive stats!")

if __name__ == "__main__":
    main()
