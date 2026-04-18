# Handoff

## Continue from here

- Start with `docs/PROJECT_STATE.md`, `docs/NEXT_STEPS.md`, `docs/DECISIONS_LOG.md`.
- Then inspect `docs/TESTING.md` and `docs/PUBLISHING.md` before doing any wider scan.
- Next run for CI validation must happen from a real git checkout with GitHub access.
- Do not spend another iteration on local CI hardening unless a real GitHub Actions run exposes a pipeline problem.
- Remote validation was re-checked again and remains blocked by missing GitHub tooling/access in this workspace.
- A reference PDO adapter now exists; the next persistence-focused iteration should inspect `src/Infrastructure/Pdo/` and `database/schema/` before touching the rest of the codebase.
- The SQLite-backed DB verification path is now confirmed in this workspace.
- The recent Average Cost mismatch was an expectation defect, not a PDO persistence/hydration defect.
- MySQL-oriented schema and DSN-driven verification path are now confirmed against a local Docker-backed MySQL server.
- Verified MySQL baseline: `8.0.45` with transaction isolation `REPEATABLE-READ`.
- Local helper for re-running the same path: `bash tools/run-mysql-verification-docker.sh test` or `composer test:db:mysql:docker`.
- PostgreSQL-oriented schema and DSN-driven verification path are now confirmed against a local Docker-backed PostgreSQL server.
- Verified PostgreSQL baseline: `16.13` with default transaction isolation `read committed`.
- Local helper for re-running the same path: `bash tools/run-postgres-verification-docker.sh test` or `composer test:db:pgsql:docker`.
- Production-focused extraction has started in `packages/warehouse-pdo-adapter/`.
- Boundary is now frozen on path A:
  - `warehouse-core` owns verified PDO runtime, reference schemas and verification tooling
  - `warehouse-pdo-adapter` owns production-facing packaging, config/factory glue, schema copies and operational docs
- Versioning strategy is now fixed:
  - both packages use semantic versioning
  - major/minor lines stay aligned while both live in the same repository
  - current rule is `warehouse-pdo-adapter 0.1.x` -> `warehouse-core ^0.1`
- Schema source of truth is fixed at root `database/schema/mysql.sql` and `database/schema/postgresql.sql`; package copies are delivery artifacts that must be updated in the same commit.
- Publishing strategy is fixed:
  - `warehouse-core` publishes directly from the monorepo root
  - `warehouse-pdo-adapter` publishes through `git subtree split`
  - monorepo bookkeeping tags for the adapter use `pdo-adapter-vX.Y.Z`
- Dry-run release findings are now concrete:
  - this is a real git checkout
  - `origin` is configured
  - `adapter-remote` is configured
  - `git subtree split --prefix=packages/warehouse-pdo-adapter HEAD` now succeeds locally
  - `composer release:rehearse` now validates committed `HEAD` in a temporary clean worktree
  - the current committed `HEAD` is now aligned with the intended release candidate and passes clean rehearsal locally
  - remote access to `origin` is still not confirmed in this environment

## First files to inspect next

- `docs/PROJECT_STATE.md`
- `docs/NEXT_STEPS.md`
- `docs/TESTING.md`
- `.github/workflows/ci.yml`
- `composer.json`
- `docker/legacy/php56/Dockerfile`
- `tools/run-legacy-tests-docker.sh`
- `src/Infrastructure/Pdo/`
- `database/schema/reference.sql`
- `database/schema/sqlite.sql`
- `database/schema/mysql.sql`
- `database/schema/postgresql.sql`
- `tools/run-db-adapter-smoke.php`
- `tests/Unit/PdoReferenceAdapterTest.php`
- `packages/warehouse-pdo-adapter/README.md`
- `packages/warehouse-pdo-adapter/docs/BOUNDARIES.md`
- `packages/warehouse-pdo-adapter/docs/PUBLISHING.md`
- `packages/warehouse-pdo-adapter/docs/COMPATIBILITY.md`
- `packages/warehouse-pdo-adapter/docs/SCHEMA_SYNC.md`
- `packages/warehouse-pdo-adapter/docs/RELEASES.md`
- `docs/RELEASE_EXECUTION.md`

## Most important open work

- Run the split CI matrix in GitHub Actions from a real git checkout
- Confirm the Docker-based legacy CI job on GitHub Actions
- Confirm the hosted SQLite-backed reference DB CI job on GitHub Actions
- Confirm cache reuse behavior in the legacy Docker job and the modern Composer jobs
- Do not reopen runtime ownership.
- If the next step is publishing, follow the compatibility/schema-sync/release docs rather than changing package structure.
- Use the chosen subtree plan; do not switch to a different monorepo publishing strategy without a new explicit decision.
- Before the next real tag/push attempt, re-run `composer release:rehearse`, then re-run the same rehearsal with `--check-remotes` from the release environment.
- Reuse the Docker-backed MySQL or PostgreSQL helpers only if you need to regression-check the existing PDO infrastructure.
- If extraction planning exposes missing persistence concerns, keep them scoped to adapter packaging rather than reopening core logic.

## Sensitive areas

- PHP 5.6 syntax compatibility
- Valuation reproducibility for average cost
- Transfer policy and cost provenance
- Reservation impact on availability versus physical stock
- Test strategy split between PHPUnit and dependency-free smoke verification
- The Docker legacy environment depends on archived Debian mirrors and should stay minimal
- CI hardening should stay understandable; avoid adding ornamental jobs or exotic caching layers
- The next CI validation step requires actual repository metadata and GitHub permissions
- The PDO adapter uses best-effort locking only; vendor-specific transaction behavior is still a hardening topic
- The next PDO step is extraction/package hardening, not another contract reshaping pass
- The runtime ownership decision is already made for now; avoid reopening it casually in the next iteration
- PostgreSQL hardening did not expose a hidden cross-driver contract gap in the current reference adapter

## Do not break

- Framework-agnostic boundaries
- Packagist-ready metadata
- Project memory workflow
- Snapshot persistence for average cost operations
- The separate legacy/modern CI strategy
- The verified Docker-based PHP 5.6 fallback path
- The remote-validation checklist in `README.md`
- The new PDO repository mappings and SQL schema contract
- The new production-focused package workspace and its boundary docs
