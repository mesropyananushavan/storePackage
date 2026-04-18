# Worklog

## Iteration 1

- Initialized package metadata, CI, PHPUnit bootstrap and top-level documentation.
- Created project memory files required for continuation.
- Created examples directory and publication scaffolding.

## Iteration 2

- Implemented contracts for repositories, valuation strategies and infrastructure abstractions.
- Implemented domain entities, value objects and required exceptions.
- Implemented FIFO, LIFO and Average Cost valuation strategies.
- Implemented application services for receipt, shipment, move, reserve, release, adjustment, availability and valuation queries.
- Added in-memory repositories and helper infrastructure.
- Added unit test files for mandatory valuation and workflow scenarios.
- Ran `php -l` across `src/`, `tests/`, `examples/`.
- Ran runtime smoke-checks for FIFO, Average Cost and move/adjust workflow flow.
- Attempted `composer install`, but PHPUnit installation was blocked by missing `ext-dom` in local PHP 8.1.

## Iteration 3

- Reworked dev/test setup instead of changing the working core.
- Added explicit dev extension requirements for PHPUnit-driven jobs.
- Added `tools/project_autoload.php` so tests and smoke scripts can run with or without `vendor/autoload.php`.
- Added `tools/run-phpunit.php` for clearer PHPUnit execution and PHAR fallback support through `PHPUNIT_BINARY`.
- Added `tools/run-smoke-tests.php` and `tools/lint.php`.
- Added Composer scripts: `lint`, `test`, `test:legacy`, `test:modern`, `test:smoke`, `verify`.
- Reworked GitHub Actions into `test-legacy`, `test-modern` and `runtime-smoke` jobs.
- Ran `composer validate --strict`.
- Ran `composer verify`.
- Confirmed `composer test` now fails with a clear message instead of a vague missing-binary error when PHPUnit is unavailable.
- Confirmed that `composer install --no-dev` from a source checkout still fails in this environment because no lock file exists and Composer resolves dev constraints.

## Iteration 4

- Added Docker-based legacy environment at `docker/legacy/php56/Dockerfile`.
- Added helper script `tools/run-legacy-tests-docker.sh`.
- Added Composer shortcut `composer test:legacy:docker`.
- Switched the CI legacy job to Docker-by-default instead of hosted PHP 5.6 setup.
- Verified that the Docker legacy image boots real PHP 5.6.40.
- Ran the full legacy Docker path successfully: Composer 2.2 + PHPUnit 5.7 + `8 tests / 39 assertions`.
- Found and fixed PHP 5.6 interface signature compatibility mismatches in infrastructure repository implementations.
- Removed generated `composer.lock` after verification to keep the repository library-oriented.

## Iteration 5

- Hardened the GitHub Actions workflow without changing package architecture.
- Added Buildx layer caching to the Docker-based legacy job.
- Added Composer download caching to the hosted modern jobs.
- Added step names, workflow-level permissions and timeout limits for clearer CI diagnostics.
- Updated `tools/run-legacy-tests-docker.sh` with `pipefail`, explicit log messages and `WAREHOUSE_LEGACY_SKIP_BUILD=1` support for faster repeat runs.
- Updated README and testing docs with a short local-checks section and the optimized legacy Docker workflow.
- Verified the skip-build legacy command locally: `WAREHOUSE_LEGACY_SKIP_BUILD=1 bash tools/run-legacy-tests-docker.sh "composer validate --strict && composer test:legacy"`.

## Iteration 6

- Attempted to prepare for remote GitHub Actions validation.
- Confirmed remote execution is not possible from this workspace because there is no `.git` repository and no `gh` CLI.
- Re-ran local CI preflight checks instead: `composer validate --strict`, `composer verify`, cached legacy Docker path via `WAREHOUSE_LEGACY_SKIP_BUILD=1`.
- Updated README and memory files with a remote-validation checklist for the next run from a real git checkout.

## Iteration 7

- Re-checked whether this workspace can trigger or inspect GitHub Actions.
- Confirmed again that remote validation is still impossible here because the workspace is not a git checkout and `gh` is not installed.
- Intentionally did not add new local CI hardening, because the current blocker is repository access rather than pipeline design.
- Refreshed project memory files so the next run can focus only on remote CI validation from a real git checkout.

## Iteration 8

- Re-read only the CI/memory files needed for the remote-validation step.
- Re-checked repository and GitHub tooling availability with `git rev-parse --is-inside-work-tree`, `git remote -v` and `gh --version`.
- Confirmed again that remote GitHub Actions validation is still impossible here because there is no `.git` directory and no `gh` CLI.
- Intentionally did not change workflow logic or add more local CI hardening, because the current blocker is access to a real GitHub-connected checkout.
- Updated project memory files so the next run can start directly with remote CI execution instead of repeating local checks.

## Iteration 9

- Added a framework-agnostic reference PDO adapter under `src/Infrastructure/Pdo/`.
- Implemented repository mappings for inventory lots, stock movements, reservations and valuation snapshots.
- Added `PdoTransactionManager` with nested-transaction pass-through and basic concurrency exception mapping.
- Added reference SQL schema files at `database/schema/reference.sql` and `database/schema/sqlite.sql`.
- Added `tools/run-db-adapter-smoke.php` and Composer shortcut `composer test:db-smoke`.
- Added `tests/Unit/PdoReferenceAdapterTest.php` as an integration-like SQLite test that skips when `ext-pdo_sqlite` is unavailable.
- Confirmed local environment has `ext-pdo` but not `ext-pdo_sqlite`, so the DB smoke path could be wired and documented but not executed here.
- Updated README and project memory files to document the adapter boundaries and remaining production-hardening work.

## Iteration 10

- Re-read only the PDO adapter, schema, smoke script and related memory files requested for the verification step.
- Re-checked DB-capable environment prerequisites.
- Confirmed again that this workspace has `ext-pdo`, but still has no `ext-pdo_sqlite` and no `WAREHOUSE_DB_DSN`.
- Intentionally did not simulate a successful DB-backed run and did not modify application services or valuation logic.
- Tightened README and testing docs with an explicit DB verification checklist so the next DB-capable run can focus only on real adapter validation.

## Iteration 11

- Re-read only the PDO adapter, schema, smoke script, adapter test and related memory files requested for the DB verification step.
- Re-checked `ext-pdo_sqlite` and `WAREHOUSE_DB_DSN`.
- Confirmed again that the workspace is still not DB-capable for end-to-end adapter verification.
- Intentionally did not modify the PDO repositories or schema, because there is no new runtime failure to fix yet.
- Refreshed README and memory files so the next DB-capable run can execute `composer test:db-smoke` and `vendor/bin/phpunit --filter PdoReferenceAdapterTest` immediately.

## Iteration 12

- Re-read only the Average Cost contract files, PDO adapter files and related memory/docs files needed for the reported adapter defect.
- Reproduced the failures in `composer test:db-smoke` and `vendor/bin/phpunit --filter PdoReferenceAdapterTest`.
- Confirmed the root cause was not PDO persistence or hydration: the domain contract already returns two lot-based allocations for Average Cost via `AbstractInventoryValuationStrategy::buildSequentialResult()`.
- Fixed the expectation layer only:
  - `tools/run-db-adapter-smoke.php`
  - `tests/Unit/PdoReferenceAdapterTest.php`
- Re-ran:
  - `composer test:db-smoke`
  - `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
  - `vendor/bin/phpunit --filter AverageCostValuationStrategyTest`
- All three checks passed.
- Updated docs to record that the SQLite-backed reference PDO path is now practically verified and that the defect was an expectation mismatch.

## Iteration 13

- Re-read only the PDO adapter, schema, smoke/test entrypoints and related memory/docs files for the MySQL hardening step.
- Checked MySQL capability in the workspace:
  - `pdo_mysql` is available
  - `WAREHOUSE_DB_DSN` is unset
  - `mysql` CLI is not installed
- Added `database/schema/mysql.sql` with MySQL-oriented choices:
  - `ENGINE=InnoDB`
  - `utf8mb4`
  - MySQL-specific indexes
  - `MEDIUMTEXT` metadata storage
  - surrogate primary key for allocation rows
- Updated `tools/run-db-adapter-smoke.php` to auto-select `database/schema/mysql.sql` for MySQL DSNs and to verify transaction rollback behavior.
- Updated `tests/Unit/PdoReferenceAdapterTest.php` to support DSN-driven execution and to assert rollback behavior.
- Re-ran the available SQLite-backed checks:
  - `composer test:db-smoke`
  - `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
  - `php tools/lint.php`
- Confirmed the adapter still passes on the SQLite reference path after MySQL-oriented hardening.
- Did not simulate MySQL success, because no live MySQL DSN is available in this workspace.

## Iteration 14

- Re-read only the PDO adapter, schema, smoke/test entrypoints and related memory/docs files for the live MySQL verification step.
- Re-checked MySQL runtime prerequisites:
  - `pdo_mysql` is available
  - `WAREHOUSE_DB_DSN`, `WAREHOUSE_DB_USER`, `WAREHOUSE_DB_PASSWORD` and `WAREHOUSE_DB_SCHEMA_FILE` are unset
  - `mysql` CLI is not installed in this workspace
- Intentionally did not run `composer test:db-smoke` or `vendor/bin/phpunit --filter PdoReferenceAdapterTest` against MySQL, because that would not be a real DB-backed verification.
- Updated README and memory files with the exact MySQL verification prerequisites so the next MySQL-capable run can start directly with smoke and adapter-level tests.

## Iteration 15

- Re-read only the PDO adapter, schema, smoke/test entrypoints and related memory/docs files for local Docker-backed MySQL verification.
- Confirmed Docker daemon access and added `tools/run-mysql-verification-docker.sh` as a minimal local helper for MySQL-backed verification.
- Added Composer shortcut `composer test:db:mysql:docker`.
- Pulled and booted Docker-backed MySQL `8.0.45`.
- Ran the real MySQL-backed verification path:
  - `bash tools/run-mysql-verification-docker.sh test`
  - internally ran `composer test:db-smoke`
  - internally ran `vendor/bin/phpunit --filter PdoReferenceAdapterTest`
- Found one MySQL-specific defect in the verification layer:
  - `PdoReferenceAdapterTest` reused the same MySQL database across test methods and reapplied schema without resetting tables first
  - smoke/test logic was made repeatable by dropping known adapter tables before schema apply
- Re-ran the Docker-backed MySQL verification successfully after the fix.
- Captured the verified MySQL baseline:
  - server version `8.0.45`
  - transaction isolation `REPEATABLE-READ`
- Updated the helper so `test` mode cleans up the container automatically unless `WAREHOUSE_MYSQL_KEEP_CONTAINER=1`.
- Verified that the helper now removes the test container automatically after a successful run.

## Iteration 16

- Re-read only the PDO adapter, schema, smoke/test entrypoints, MySQL helper and related memory/docs files for PostgreSQL hardening.
- Confirmed Docker daemon access and checked host prerequisites:
  - `pdo_pgsql` is not available on host PHP
  - PostgreSQL verification therefore needed a containerized PHP runtime rather than a host DSN run
- Added `database/schema/postgresql.sql`.
- Added `docker/db-verification/php81-pgsql/Dockerfile`.
- Added `tools/run-postgres-verification-docker.sh`.
- Added Composer shortcut `composer test:db:pgsql:docker`.
- Updated `tools/run-db-adapter-smoke.php` and `tests/Unit/PdoReferenceAdapterTest.php` so `pgsql:` DSNs auto-select `database/schema/postgresql.sql`.
- Built the PostgreSQL verification PHP image and ran the real PostgreSQL-backed verification path:
  - `bash tools/run-postgres-verification-docker.sh test`
- Found one PostgreSQL-specific defect in the helper layer:
  - the helper passed a host absolute schema path into the container
  - fixed by switching container execution to `/app/database/schema/postgresql.sql`
  - also silenced Composer/git ownership noise inside the container by marking `/app` as a safe git directory
- Re-ran the PostgreSQL verification successfully after the fix.
- Captured the verified PostgreSQL baseline:
  - server version `16.13`
  - default transaction isolation `read committed`
- Verified the Composer shortcut:
  - `composer test:db:pgsql:docker`
- Verified that the helper removes the PostgreSQL container and helper network automatically after a successful run.

## Iteration 17

- Re-read only the memory files, PDO adapter files, schema files and verification helpers required for extraction planning.
- Chose a low-risk staged extraction strategy instead of moving the verified PDO runtime immediately.
- Added `packages/warehouse-pdo-adapter/` as an in-repository production-focused package workspace.
- Added `PdoAdapterConfig` and `PdoAdapterFactory` so deployment-facing connection/bootstrap concerns can evolve separately from the reference runtime.
- Added production-facing schema copies under `packages/warehouse-pdo-adapter/resources/schema/` for MySQL and PostgreSQL.
- Added package-local docs for adapter boundaries, migration ownership and operational defaults.
- Updated root README and project memory files to separate:
  - root reference runtime and verification tooling
  - extracted package workspace and production-focused concerns
- Did not move `src/Infrastructure/Pdo/` out of the root package yet, because the runtime boundary is not frozen.

## Iteration 18

- Re-read only the memory files, root PDO runtime files and extracted package workspace needed for boundary freeze.
- Chose boundary path A as the stable structure:
  - `warehouse-core` keeps ownership of verified runtime classes, reference schemas and verification tooling
  - `warehouse-pdo-adapter` stays a production-facing packaging layer on top of that runtime
- Updated README, architecture, publishing docs and handoff notes so runtime/schema/tooling ownership is explicit.
- Kept business logic and verified PDO runtime code untouched.
- Prepared the next step as packaging cleanup and publishing/versioning discipline rather than runtime relocation.

## Iteration 19

- Re-read only the packaging, publishing and schema files needed for release-discipline cleanup.
- Chose the package versioning strategy:
  - both packages use semantic versioning
  - major/minor compatibility lines stay aligned while both live in the same repository
  - current adapter rule is `warehouse-pdo-adapter 0.1.x` -> `warehouse-core ^0.1`
- Chose the schema policy:
  - root `database/schema/mysql.sql` and `database/schema/postgresql.sql` are source of truth
  - package-local MySQL/PostgreSQL schemas are controlled copies
  - schema copies must be updated in the same commit as the root change
- Added package-local docs for compatibility, schema sync and release checklist.
- Synchronized `packages/warehouse-pdo-adapter/resources/schema/*.sql` with the current verified root vendor-specific schemas.

## Iteration 20

- Re-read only the publishing, schema and package metadata/docs needed for real release planning.
- Chose `git subtree split` as the monorepo publishing strategy.
- Fixed the publication model:
  - `warehouse-core` publishes from the monorepo root
  - `warehouse-pdo-adapter` publishes from a dedicated subtree-split repository
- Added a concrete execution plan at `docs/RELEASE_EXECUTION.md`, including:
  - split commands
  - tag naming
  - push order
  - schema-sync gate before adapter release
- Updated root and package docs so the chosen release choreography is explicit rather than implied.

## Iteration 21

- Re-read only the release-flow files needed for a dry-run release attempt.
- Confirmed this workspace is now a real git checkout on `main` with `origin` configured.
- Confirmed local branch/tag creation works by creating and deleting temporary dry-run refs.
- Confirmed `warehouse-core` dry-run prerequisites are only partially satisfied:
  - git checkout exists
  - tag/branch creation works
  - worktree is dirty
  - remote reachability to `origin` failed due DNS resolution in this environment
- Confirmed `warehouse-pdo-adapter` dry-run is blocked more concretely:
  - `adapter-remote` is missing
  - `packages/warehouse-pdo-adapter/` is not present in committed `HEAD`

## Iteration 22

- Reviewed the outstanding release-discipline, schema-sync, CI and packaging changes in the working tree and grouped the intended release-candidate state into one commit.
- Created commit `cc4fb37` with the release hardening, rehearsal tooling, schema-copy sync, CI coverage and related documentation updates.
- Confirmed the workspace became clean after that commit.
- Ran `composer release:rehearse` and verified that committed `HEAD` now passes clean-checkout release rehearsal.
- Re-ran `php tools/run-release-rehearsal.php --check-remotes` and confirmed the only failing part in this environment is remote reachability to `origin` and `adapter-remote`.
- Updated release docs to make those blockers explicit instead of leaving them implicit.

## Added files

- `composer.json`
- `.gitignore`
- `LICENSE`
- `CHANGELOG.md`
- `phpunit.xml.dist`
- `.github/workflows/ci.yml`
- `examples/config.php`
- `examples/bootstrap.php`
- `docs/*.md`
- `src/Contracts/*.php`
- `src/Domain/**/*.php`
- `src/Application/**/*.php`
- `src/Infrastructure/**/*.php`
- `tests/Doubles/*.php`
- `tests/Unit/*.php`
- `tools/*.php`
- `docker/legacy/php56/Dockerfile`
- `tools/run-legacy-tests-docker.sh`
- `database/schema/reference.sql`
- `database/schema/sqlite.sql`
- `database/schema/mysql.sql`
- `src/Infrastructure/Pdo/*.php`
- `tools/run-db-adapter-smoke.php`
- `tests/Unit/PdoReferenceAdapterTest.php`

## Modified files

- `README.md`
- `composer.json`
- `.gitignore`
- `tests/bootstrap.php`
- `.github/workflows/ci.yml`
- `tools/run-legacy-tests-docker.sh`
- `src/Infrastructure/InMemory/InMemoryInventoryLotRepository.php`
- `src/Infrastructure/InMemory/InMemoryInventoryValuationSnapshotRepository.php`
- `src/Infrastructure/InMemory/InMemoryReservationRepository.php`
- `src/Infrastructure/InMemory/InMemoryStockMovementRepository.php`
- `src/Infrastructure/Stub/DatabaseInventoryLotRepositoryStub.php`
- `examples/bootstrap.php`
- `src/Infrastructure/Stub/DatabaseInventoryLotRepositoryStub.php`
- `docs/PROJECT_STATE.md`
- `docs/NEXT_STEPS.md`
- `docs/ARCHITECTURE.md`
- `docs/DECISIONS_LOG.md`
- `docs/WORKLOG.md`
- `docs/TESTING.md`
- `docs/PUBLISHING.md`
- `docs/HANDOFF.md`

## Problems found

- Local environment lacks `ext-dom` and `ext-xmlwriter`, so `phpunit/phpunit` still cannot be installed here.
- Full CI and Packagist publication flow were not exercised from this workspace.
- `composer install --no-dev` in a source checkout without `composer.lock` still resolves dev requirements, so it is not a reliable local fallback for this library repo.
- Archived Debian mirrors required small Dockerfile hardening for the PHP 5.6 image.
- PHP 5.6 runtime enforcement exposed interface/implementation signature mismatches that were not visible in the PHP 8.1 smoke-only path.
- Remote GitHub Actions cache behavior and runner-side execution still have not been exercised from this workspace.
- Remote GitHub Actions cannot be triggered from this workspace because repository metadata and GitHub CLI access are absent.
- The last reported Average Cost adapter failure was caused by incorrect expectation of a single synthetic allocation; the actual domain contract requires multiple lot-based allocations with averaged unit cost.
- Host PHP still lacks `ext-pdo_pgsql`, so PostgreSQL verification currently depends on the Docker helper path rather than direct host execution.
- MySQL-backed verification is now confirmed locally; the remaining persistence gap is production-focused extraction rather than missing MySQL coverage.
- PostgreSQL-backed verification is now confirmed locally; the remaining persistence gap is production-focused extraction rather than missing PostgreSQL coverage.
- PostgreSQL-backed verification is now confirmed locally; the next persistence step is extraction planning rather than another vendor-baseline proof.

## Pending

- Run the GitHub Actions matrix from a real git checkout
- Validate CI matrix behavior for hosted modern jobs
- Execute the reference PDO adapter against a MySQL DSN

## Iteration 25

- Re-read only the release/publishing docs, package release notes, Composer scripts and existing tools needed for release dry-run hardening.
- Added `tools/run-release-dry-run.php` as a reproducible preflight for release work.
- Added Composer shortcut `composer release:dry-run`.
- The dry-run now checks:
  - real git worktree presence
  - clean-worktree discipline unless `--allow-dirty` is used intentionally
  - strict Composer validation for root and adapter manifests
  - schema-copy sync between root and package delivery files
  - `origin` and `adapter-remote` configuration
  - adapter package presence in committed `HEAD`
  - local `git subtree split --prefix=packages/warehouse-pdo-adapter HEAD`
- Found a real release-discipline issue while wiring the tool:
  - package schema copies differed from the root schemas due only to extra header comments
  - removed those comments so the package copies are now exact source-of-truth copies and dry-run checks fail only on meaningful drift
- Updated release/publishing/handoff docs so they now reference the dry-run command and reflect the actual current git state:
  - `adapter-remote` is configured locally
  - subtree split works locally
  - remaining blockers are clean worktree, remote reachability and real GitHub Actions confirmation

## Iteration 26

- Re-read only the release dry-run tool, release docs and package release checklists needed for clean-checkout rehearsal.
- Added `tools/run-release-rehearsal.php`.
- Added Composer shortcut `composer release:rehearse`.
- The rehearsal now:
  - resolves committed `HEAD`
  - creates a temporary clean `git worktree`
  - runs the strict preflight against that clean checkout
  - can optionally add remote reachability checks via `--check-remotes`
  - removes the temporary worktree automatically unless `--keep-worktree` is used
- Confirmed the rehearsal semantics are intentionally stricter than `--allow-dirty`:
  - it ignores uncommitted workspace changes
  - this makes it suitable as the final pre-tag confirmation step
- Updated root and package release docs so the canonical path is now:
  - commit intended release candidate
  - run `composer release:rehearse`
  - run `php tools/run-release-rehearsal.php --check-remotes` from the release environment
