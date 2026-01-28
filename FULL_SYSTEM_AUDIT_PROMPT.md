# OPM Stats System - Deep Dive Audit & Perfection Protocol

**Role:** You are a Senior Principal Software Architect and Security Engineer with expertise in Go, PHP (SMF), Docker, and High-Performance Data Systems (ClickHouse/Redis/PostgreSQL).

**Objective:** Conduct a ruthless, line-by-line deep dive of the entire `opm-stats-system` codebase. Your goal is to guarantee a **100% working, zero-friction deployment** on Portainer, ensuring the data flow from "Game Server" -> "API" -> "Database" -> "SMF Frontend" is flawless.

**Context:**
- **Repo:** `opm-stats-system` (Main), `opm-stats-api` (Submodule/Repo), `opm-stats-web` (Directory).
- **Deployment:** Two Docker stacks (`opm-stats-api-dev` and `dev-moh-central`) sharing an external network `opm-network-dev`.
- **Core Requirement:** The system must work "out of the box" when these stacks are deployed.

---

## 🔍 Phase 1: Infrastructure & Configuration Audit
*Critique the Docker composition and environment injection.*
1.  **Environment Variables:** Verify that `.env.api-dev` and `.env.web-dev` cover *every single runtime requirement*. Are defaults safe? Are secrets actually secret?
2.  **Networking:** Confirm service discovery mechanics (e.g., `MOHAA_API_URL` vs `MOHAA_API_PUBLIC_URL`). Will the PHP container (internal `http://opm-stats-api:8080`) and the user's browser (public `https://...`) both connect successfully?
3.  **Volume Management:** Are database persistence volumes correctly defined? Will data survive a container rebuild?
4.  **Healthchecks:** Do the containers wait for dependencies? Analyze `depends_on` and `healthcheck` definitions in `docker-compose.yml` files.

## 💾 Phase 2: Database Layer & Schema Integrity
*Inspect SQL migrations and data structures.*
1.  **Schema Logic:** Audit `opm-stats-api/migrations` (Postgres & ClickHouse). Are the data types optimal (e.g., `UInt8` vs `Int32`, `DateTime64` in ClickHouse)?
2.  **SMF Integration:** Inspect `opm-stats-web/init-db` and installer SQL. Does the plugin installation (`mohaa_install.php`) correctly modify the SMF tables?
3.  **Relationships:** Verify foreign keys and indices. Is the mapping between `smf_members` (MySQL) and `player_profiles` (Postgres) robust?

## ⚙️ Phase 3: API Backend (Go) Deep Dive
*Analyze `opm-stats-api` code logic.*
1.  **Concurrency & Safety:** Inspect the Worker Pool (`internal/worker`). Is the channel handling robust? Can it handle a flood of events without crashing?
2.  **Stats Aggregation:** Trace the logic in `internal/logic`. Is the math correct for K/D ratios, accuracy, and win rates? Are edge cases (divide by zero) handled?
3.  **Error Handling:** Are database connection failures handled gracefully? Is logging sufficient (`internal/logger`)?
4.  **Security:** Audit the JWT implementation and input validation in `internal/handlers`. Is the Ingestion API secured against spoofed data?

## 🎨 Phase 4: Frontend & SMF Integration (PHP/JS)
*Inspect `opm-stats-web` source code.*
1.  **Plugin Architecture:** Audit `mohaa_master_install.php` and `Sources/Mohaa*.php`. Are hooks registered correctly?
2.  **Display Logic:** Review `Themes/default/Mohaa*.template.php`. Is the HTML semantic? Are charts (ApexCharts/Chart.js) implemented correctly?
3.  **Data Fetching:** Analyze how the PHP proxy handles API requests. Does it fail gracefully if the API is down?
4.  **XSS/Security:** Are variables escaped before output in the SMF templates?

## 🚀 Phase 5: Installer & Bootstrapping Mechanism
*The "First Run" Experience.*
1.  **Entrypoint Logic:** Deep dive into `opm-stats-web/docker-entrypoint.sh`. Does it handle the "chicken and egg" problem (installing the plugin before SMF is fully ready)?
2.  **Idempotency:** Run the installer logic mentally twice. Will it crash on the second run, or gracefully skip?
3.  **Dependency Patching:** Verification of the `sed` patches applied to `mohaa_install.php` at runtime. Are we correctly injecting the SMF db dependencies?

---

## 📝 Output Requirements
For every issue found, provide:
1.  **Severity:** (Critical/High/Medium/Low)
2.  **Location:** File path and line number.
3.  **Issue:** Description of the logic flaw, race condition, or bug.
4.  **Fix:** Exact code change required.

**Final Deliverable:** A prioritized list of fixes to guarantee the system works perfectly upon deployment.
