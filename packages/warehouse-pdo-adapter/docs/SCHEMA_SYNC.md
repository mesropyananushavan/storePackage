# Schema Sync Policy

## Source of truth

- `database/schema/mysql.sql` and `database/schema/postgresql.sql` in the root package are the source of truth.
- `packages/warehouse-pdo-adapter/resources/schema/mysql.sql` and `.../postgresql.sql` are controlled copies for production-facing delivery.
- `database/schema/reference.sql` and `database/schema/sqlite.sql` remain root-only assets for the reference adapter and verification tooling.

## Sync rule

- Whenever a root MySQL or PostgreSQL schema file changes, the corresponding package copy must be updated in the same commit.
- No package release should ship with intentional drift between the root schema and the package copy unless the difference is documented explicitly in release notes.

## Manual sync checklist

1. Update the root vendor-specific schema file.
2. Copy the same change into `packages/warehouse-pdo-adapter/resources/schema/`.
3. Keep the source-of-truth comment at the top of the package copy.
4. Review the diff side-by-side before release.
5. Mention schema-copy changes in the adapter package release notes.

## Why there is no automation yet

- Current need is packaging discipline, not a new automation subsystem.
- A documented same-commit sync rule is sufficient until the package split or release tooling becomes more formal.
