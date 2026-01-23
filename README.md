# OpenMOHAA Stats System

A high-throughput competitive statistics and tournament infrastructure for Medal of Honor: Allied Assault.

## 📂 Project Structure

This workspace is now organized into modular repositories:

### [opm-stats-api/](./opm-stats-api/)
**The Backend Core** (Go).
-   Rest API handling global event ingestion.
-   Databases (PostgreSQL, ClickHouse, Redis).
-   Worker pools and analytic processing.
-   **Run here**: `docker compose up -d` (Production) or `./start.sh` (Dev)

### [opm-stats-smf-integration/](./opm-stats-smf-integration/)
**The Web Frontend** (PHP/SMF).
-   Simple Machines Forum 2.1 Plugins.
-   Player Profiles, Leaderboards, Match History (Frontend).
-   **Build**: `./build_smf_package.sh` (Creates zip for SMF Package Manager)
-   **Dev Note**: Contains local `smf-mohaa` environment and distributable `smf-plugins`.

### [opm-stats-game-scripts/](./opm-stats-game-scripts/)
**The Data Source** (Morpheus Script).
-   `.scr` scripts running on the Game Server.
-   Captures engine events and transmits to API.

### [opm-stats-web/](./opm-stats-web/)
**Standalone Web Components** (if applicable).
-   Modern web frontend components (React/Vue/etc) separate from SMF.

### [opm-stats-cli-tools/](./opm-stats-cli-tools/)
**Utilities**.
-   CLI tools for server management or data operations.

### [opm-stats-docs/](./opm-stats-docs/)
**Documentation**.
-   Comprehensive project documentation, architecture, and audit logs.

---

## 🤖 AI Assistants & Developers
Refer to [AGENTS.md](AGENTS.md) for global architectural standards.
