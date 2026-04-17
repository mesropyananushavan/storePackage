# Migration Strategy

## Ownership

- The root package still ships `database/schema/*.sql` as the verified reference baseline.
- This package workspace carries production-facing schema copies under `resources/schema/`.
- Root `database/schema/mysql.sql` and `database/schema/postgresql.sql` are the source of truth for the copied package schemas.
- Consumers should treat these files as bootstrap references, not as a full migration runner.

## Recommended production approach

1. Copy the vendor-specific schema into the application's migration system.
2. Version schema changes in the consuming application's release process.
3. Keep table names aligned with the repository table-name map unless you intentionally override them.
4. Add target-environment indexes only after measuring real workload patterns.

## Not included yet

- Migration runner
- Drift detection
- Online schema change tooling
- Zero-downtime rollout strategy
