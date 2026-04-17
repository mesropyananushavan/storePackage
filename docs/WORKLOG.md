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

## Pending

- Run the GitHub Actions matrix from a real git checkout
- Validate CI matrix behavior for hosted modern jobs
- Add non-memory persistence adapters when needed
