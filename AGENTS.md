<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# AGENTS.md

This file provides guidance to all AI agents (Claude, Codex, Gemini, etc.) working with code in this repository.

## Overview

Nextcloud Notes app: a PHP backend (Nextcloud app, `OCA\Notes` namespace) with a Vue 3 + Pinia frontend. Notes are **not** stored in the database — each note is a Markdown/text file in the user's Nextcloud files (default folder `Notes`). The database only holds a per-note metadata cache (`lib/Db/Meta*`) used for ETags and change tracking.

## Nextcloud Contribution Policy

All contributions generated or assisted by this agent must fully comply with:

- **[AI Contribution Policy](https://github.com/nextcloud/.github/blob/master/AI_POLICY.md)** - the primary reference for AI-specific rules, covering disclosure, author accountability, communication, security, licensing, code quality, and autonomous agent behavior.
- **[Contribution Guidelines](https://github.com/nextcloud/.github/blob/master/CONTRIBUTING.md)** - covering testing requirements, the Developer Certificate of Origin (DCO), license headers, conventional commits, and translations. These apply in full to all contributions regardless of how they were produced.

### What this agent must always do

- Add an `Assisted-by: AGENT_NAME:MODEL_VERSION` git trailer to every commit containing AI-assisted content.
- Ensure every pull request includes a disclosure of AI tool use in the PR description.
- Produce focused, scoped pull requests that address exactly one concern. Do not touch unrelated files or introduce incidental refactors.
- Verify all dependencies against actual package registries before suggesting them. Do not use hallucinated or unverified package names.
- Explicitly inform the contributor when any action they are about to take, or have taken, would violate the AI Contribution Policy or the Contribution Guidelines. Do not silently proceed. State which rule is at risk and what the contributor should do instead.
- Warn the contributor if a pull request is growing too large. A PR approaching several thousand lines of changed code is a signal that it should be split into smaller, focused PRs. Suggest a logical split before the PR is opened, not after.
- Recommend opening a ticket for discussion before starting implementation whenever a feature or change is sufficiently complex - for example when it touches multiple subsystems, requires architectural decisions, or the right approach is not yet clear. A ticket allows maintainers and the contributor to align on direction before code is written, avoiding wasted effort on a PR that may be rejected or require fundamental rework.

### What this agent must never do

- Open issues, submit pull requests, post review comments, or send security reports autonomously. Every contribution must be reviewed and submitted by a human.
- Add `Signed-off-by` tags to commits. Only the human contributor can certify the Developer Certificate of Origin.
- Generate or submit security reports without independent human verification. Report verified vulnerabilities via [HackerOne](https://hackerone.com/nextcloud), not as GitHub issues.
- Write PR descriptions, review comments, or issue reports on behalf of the contributor. These must be in the contributor's own words.
- Fully automate the resolution of issues labeled [`good first issue`](https://github.com/issues?q=org%3Anextcloud+label%3A%22good+first+issue%22) or similar beginner-friendly labels.
- Submit code that has not been reviewed and cleaned up by the contributor. Dead code, redundant logic, excessive comments, and unrelated changes must be removed before submission.

## Commands

Setup: `make dev-setup` (runs `composer install` + `npm install`). Requires PHP 8.2+, Node 24, npm 11.

### Frontend (webpack builds `src/` into `js/`)
- `npm run build` — production build
- `npm run dev` / `npm run watch` — development build (once / on change)
- `npm run lint` / `npm run lint:fix` — ESLint over `src` and `playwright`
- `npm run stylelint` / `npm run stylelint:fix`

### PHP
- `composer run cs:check` / `composer run cs:fix` — php-cs-fixer (Nextcloud coding standard)
- `composer run psalm` — static analysis
- `composer run phan` — static analysis (CI uses `make lint-php-phan`)
- `make lint` runs everything (PHP + JS + CSS + info.xml); `make lint-fix` auto-fixes

### Tests
There are no PHP unit tests. Two integration test suites exist:

- **API tests** (`tests/api/`): PHPUnit tests that make HTTP requests via Guzzle against a **running Nextcloud server at `http://localhost:8080`** with the app enabled and a user `test`/`test`. Run with `make test-api`. Run a single test: `phpunit --bootstrap vendor/autoload.php --filter testMethodName tests/api/APIv1Test.php`
- **Playwright e2e** (`playwright/e2e/`): `npm run test:e2e` (or `test:e2e:ui`). Automatically starts a Nextcloud Docker container on port 8089 (requires Docker; up to 5 min for first start). Tests run with a single worker on purpose — the bundled server uses SQLite and flakes under parallel logins.

## Architecture

### Backend (`lib/`)
- Two parallel controller stacks share logic via `lib/Controller/Helper.php`:
  - `NotesController` — internal endpoints for the Vue frontend (`/notes/...`)
  - `NotesApiController` — the **public versioned REST API** (`/api/v0.2|v1/...`, attachments on v1.4) used by the Android/iOS and third-party clients. It is a stability contract documented in `docs/api/` — changes must stay backward compatible and be reflected there (and in `lib/AppInfo/Capabilities.php` for new API versions).
- Routes are declared in `appinfo/routes.php`.
- `lib/Service/NotesService.php` is the core: resolves the notes folder, wraps files in `Note` objects (`Note`/`MetaNote` are file wrappers, not entities). `MetaService` maintains the DB metadata cache; `NoteUtil`/`TagService` handle file/tag plumbing. Errors are communicated via typed exceptions in `lib/Service/` which `Helper` maps to HTTP status codes.
- `ChunkCursor` + ETags implement chunked/pruned note listing for large collections (see `docs/api/README.md`).
- App wiring (event listeners, dashboard widget, search provider, reference provider) lives in `lib/AppInfo/Application.php`.

### Frontend (`src/`)
- Entry points: `main.js` (main app), `dashboard.js` (dashboard widget), `config.js` (admin settings) — one webpack bundle each.
- State lives in three Pinia stores (`src/stores/app.js`, `notes.js`, `sync.js`), aggregated by `src/store.js`. `src/NotesService.js` contains the server-communication layer including the sync queue and conflict handling (`ConflictSolution.vue`); components dispatch through it rather than calling axios directly.
- Note editing has three modes: `EditorEasyMDE.vue` (rich md editing), `EditorMarkdownIt.vue` (preview), `EditorPlain.vue`.

## Conventions

- **Conventional commits** are enforced on PRs by CI (e.g. `fix(l10n): ...`, `feat: ...`).
- Every file needs an SPDX license header (`SPDX-FileCopyrightText` + `SPDX-License-Identifier: AGPL-3.0-or-later`); CI enforces REUSE compliance.
- `l10n/` translation files are synced from Transifex — never edit them manually.
- The minimum Nextcloud version in `appinfo/info.xml` must stay consistent with `composer.json`'s `nextcloud/ocp` branch; `php tests/nextcloud-version.php` checks this (part of `make lint`).
- The README description paragraph must stay synchronized with `appinfo/info.xml`.
