# Skill: Build OpenMOHAA

Build the OpenMOHAA game engine from source with MQTT support.

## Prerequisites
- CMake 3.16+
- C++17 compiler (GCC 9+, Clang 10+, MSVC 2019+)
- Linux: `build-essential cmake libsdl2-dev libopenal-dev libcurl4-openssl-dev`

## Source Location
```
/run/media/elgan/evo/dev/openmohaa-central/
```

## Build Steps

### Linux Debug Build
```bash
cd /run/media/elgan/evo/dev/openmohaa-central
mkdir -p build && cd build
cmake .. -DCMAKE_BUILD_TYPE=Debug -DUSE_MQTT=ON
cmake --build . -- -j$(nproc)
```

### Linux Release Build
```bash
cd /run/media/elgan/evo/dev/openmohaa-central
mkdir -p build && cd build
cmake .. -DCMAKE_BUILD_TYPE=Release -DUSE_MQTT=ON
cmake --build . -- -j$(nproc)
```

### Key CMake Options
| Option | Default | Description |
|--------|---------|-------------|
| `USE_MQTT` | ON | MQTT telemetry/messaging support |
| `USE_HTTP` | ON | HTTP download support |
| `BUILD_SERVER` | ON | Build dedicated server |
| `BUILD_CLIENT` | ON | Build game client |
| `BUILD_GAME_LIBRARIES` | ON | Build game DLLs (.so/.dll) |

### MQTT Build Verification
After building, verify MQTT support:
```bash
# Check that game library exports mqtt commands
nm -D build/main/game*.so | grep -i mqtt
# Should show: MqttConnect, MqttPublish, MqttSubscribe, etc.
```

### Build Outputs
```
build/opmohaa          # Client executable
build/opmohaaded       # Dedicated server
build/main/game*.so    # Game library (basegame)
build/mainta/game*.so  # Game library (missionpack)
```

## Troubleshooting
- If MQTT is disabled: `cmake .. -DUSE_MQTT=OFF` (only needed when broker unavailable)
- Build errors in mqttclient.cpp: Ensure C++17 is enabled (`-std=c++17`)
- Missing SDL2: `sudo apt install libsdl2-dev`
