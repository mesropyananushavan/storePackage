# Project State

## Goal

Build a production-oriented, framework-agnostic Composer package for warehouse automation with legacy-compatible PHP support.

## Current status

- Core package is implemented as a reusable library with contracts, domain entities, application services and in-memory infrastructure.
- Reference PDO-based DB adapter is implemented for lots, movements, reservations, valuation snapshots and transactions.
- Reference PDO-based DB adapter is now verified end-to-end against `sqlite::memory:` for the seeded smoke and adapter-level PHPUnit path.
- MySQL-oriented schema and DSN-driven verification path have been added to the reference PDO adapter.
- MySQL-backed verification is now confirmed locally through a Docker-backed MySQL 8.0.45 environment.
- Local MySQL verification uses `tools/run-mysql-verification-docker.sh` and `database/schema/mysql.sql`.
- The MySQL verification context is recorded as server version `8.0.45` with transaction isolation `REPEATABLE-READ`.
- PostgreSQL-backed verification is now confirmed locally through a Docker-backed PostgreSQL 16.13 environment.
- Local PostgreSQL verification uses `tools/run-postgres-verification-docker.sh` and `database/schema/postgresql.sql`.
- The PostgreSQL verification context is recorded as server version `16.13` with transaction isolation `read committed`.
- Production-focused adapter extraction has started inside the same repository under `packages/warehouse-pdo-adapter/`.
- Boundary is now frozen on the safer path: the verified PDO runtime stays in `src/Infrastructure/Pdo/` inside `warehouse-core`.
- `packages/warehouse-pdo-adapter/` now acts as a production-facing packaging layer that owns config/factory scaffolding, production-facing schema copies and operational docs.
- Release/versioning strategy is now documented for both packages, with aligned major/minor compatibility lines.
- Schema source of truth is now frozen at root `database/schema/mysql.sql` and `database/schema/postgresql.sql`; package-local schema files are controlled delivery copies.
- Monorepo publishing strategy is now chosen: `warehouse-core` publishes from root, `warehouse-pdo-adapter` publishes via `git subtree split`.
- Dry-run release execution was attempted from a real git checkout.
- Dry-run confirmed local git ref creation works, but full release choreography is still blocked by release-environment issues:
  - worktree is dirty
  - `adapter-remote` is not configured
  - `packages/warehouse-pdo-adapter/` is not yet present in committed `HEAD`, so `git subtree split` finds no revisions
  - remote reachability to `origin` failed in this environment due DNS/network resolution
- Project memory files are in place and now reflect actual code.
- Syntax lint passed for all PHP files through `composer verify`.
- Runtime smoke-checks passed for FIFO, Average Cost and move/adjust workflow flow through `composer verify`.
- `composer validate --strict` passes.
- Test/dev setup is now split into explicit legacy and modern paths in CI and docs.
- Docker-based legacy PHP 5.6 path is implemented and verified end-to-end.
- Full legacy Docker run passed with PHP 5.6.40 and PHPUnit 5.7.27.
- CI workflow now uses Buildx layer caching for the legacy image and Composer download caching for modern jobs.
- Reused-image legacy Docker run with `WAREHOUSE_LEGACY_SKIP_BUILD=1` is also verified locally.
- Remote GitHub Actions execution is not possible from this workspace because there is no `.git` repository and no `gh` CLI.
- Remote GitHub Actions availability was re-checked in the current iteration and remains unavailable for the same reason.
- Workflow preconditions were re-checked again in the current iteration; remote validation is still blocked by missing repository metadata and GitHub tooling rather than by CI configuration.

## Ready

- Composer metadata and publication scaffolding
- Contracts for repositories, transaction, clock, logger, events, ID generation and valuation strategy
- Domain entities and exceptions
- FIFO, LIFO and Average Cost valuation strategies
- Application services for receipt, ship, move, reserve, release, adjust, availability and valuation queries
- In-memory repositories and infrastructure helpers
- Unit test files covering mandatory costing and audit scenarios
- Reference SQL schema for portable RDBMS usage and SQLite smoke usage
- README, publishing docs and project memory files

## Partially ready

- Host-local PHPUnit execution outside Docker
- CI verification across all target PHP versions
- Publishing readiness validation against a real VCS/registry flow
- Real release execution from a git-connected and release-ready environment

## Not implemented yet

- Framework-specific adapter packages
- HTTP / barcode integrations beyond stubs

## Constraints

- PHP syntax/runtime compatibility must remain valid for PHP 5.6, 7.4 and 8.1.
- Core must stay framework-agnostic.
- Numeric costing uses float arithmetic with rounding to 6 decimals.
- Local PHP 8.1 environment is missing `ext-dom` and `ext-xmlwriter`, so PHPUnit dev dependencies still cannot be installed here.
- Local PHP environment now exposes both `ext-pdo` and `ext-pdo_sqlite`, so the SQLite-backed reference DB checks can run in this workspace.
- Local PHP environment also exposes `ext-pdo_mysql`, and the workspace now has a Docker-based helper path for self-hosted MySQL verification without external DSN provisioning.
- Local PHP environment does not expose `ext-pdo_pgsql`, so PostgreSQL verification depends on the Docker-based helper path rather than the host PHP runtime.
- Source-checkout `composer install --no-dev` without a lock file still resolves dev constraints, so local fallback uses `composer verify` instead.
- Legacy Docker build depends on Docker daemon access and archived Debian mirrors during image build.
- This workspace cannot trigger or inspect GitHub Actions directly because it is not attached to a git remote checkout.

## Supported PHP range

- Target: `>=5.6 <8.2`

## Valuation status

- FIFO: implemented and covered by tests
- LIFO: implemented and covered by tests
- Average Cost: implemented, snapshot persistence added, covered by tests

## Publish readiness

- Structurally ready for Packagist / Private Packagist
- Legacy PHPUnit execution is confirmed through Docker
- Reference PDO adapter and SQL schema are included as optional infrastructure
- Reference PDO adapter is now confirmed on SQLite, MySQL and PostgreSQL paths
- Vendor-specific hardening is now sufficient for reference-adapter confidence; remaining work is release execution discipline, schema-copy sync discipline and deeper persistence tuning
- Final release is still blocked on green GitHub Actions confirmation for `test-legacy`, `test-modern` and `runtime-smoke` from a real repository checkout
- First real package publication is also blocked until the adapter package files are committed, `adapter-remote` exists and remotes are reachable
