# Adapter Boundaries

## Reference vs production scope

- `src/Infrastructure/Pdo/` in the root package remains the verified reference runtime.
- `tools/run-db-adapter-smoke.php`, `tools/run-mysql-verification-docker.sh` and `tools/run-postgres-verification-docker.sh` remain verification tooling for the reference runtime.
- `packages/warehouse-pdo-adapter/` is the production-facing package boundary on top of that runtime.

## What this workspace owns now

- Packaging metadata for a dedicated adapter package
- Connection/config factory examples
- Production-oriented schema copies for MySQL and PostgreSQL
- Operational notes for locking, migration ownership and deployment defaults
- Publishing-facing bootstrap guidance for consumers that want more than the reference adapter

## What stays in the core package by decision

- The actual plain-PDO repository implementations
- The reference smoke and adapter-level verification path
- Cross-driver regression helpers used to keep the reference runtime honest
- Reference schema files used by verification tooling

## Frozen boundary

- The verified PDO repository classes remain in `warehouse-core`.
- This package layers bootstrap/schema/docs concerns on top of that runtime.
- A runtime move is out of scope unless a later iteration opens a dedicated deprecation plan.
