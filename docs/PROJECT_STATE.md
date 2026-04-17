# Project State

## Goal

Build a production-oriented, framework-agnostic Composer package for warehouse automation with legacy-compatible PHP support.

## Current status

- Core package is implemented as a reusable library with contracts, domain entities, application services and in-memory infrastructure.
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
- README, publishing docs and project memory files

## Partially ready

- Host-local PHPUnit execution outside Docker
- CI verification across all target PHP versions
- Publishing readiness validation against a real VCS/registry flow

## Not implemented yet

- Database-backed repositories
- Framework-specific adapter packages
- HTTP / barcode integrations beyond stubs

## Constraints

- PHP syntax/runtime compatibility must remain valid for PHP 5.6, 7.4 and 8.1.
- Core must stay framework-agnostic.
- Numeric costing uses float arithmetic with rounding to 6 decimals.
- Local PHP 8.1 environment is missing `ext-dom` and `ext-xmlwriter`, so PHPUnit dev dependencies still cannot be installed here.
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
- Final release is still blocked on green GitHub Actions confirmation for `test-legacy`, `test-modern` and `runtime-smoke` from a real repository checkout
