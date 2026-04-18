# Next Steps

## Highest priority

1. Keep the frozen boundary and release discipline intact: `warehouse-core` owns runtime plus verification tooling, `warehouse-pdo-adapter` owns production-facing packaging, schema copies and operational docs.
2. Before the next publishing attempt:
   - commit the intended release candidate
   - run `composer release:rehearse`
   - run `php tools/run-release-rehearsal.php --check-remotes` from a release-ready environment
   - if rehearsal still fails, treat that as committed-history drift and fix the committed release candidate before any tag/push step
   - run from an environment that can resolve and reach the remotes
3. Run the GitHub Actions matrix from a real git checkout and confirm `test-legacy`, `test-modern`, `runtime-smoke` and `test-db-reference` jobs on real runners before any real public release.
4. If the Docker-based legacy job is still slow in Actions after Buildx caching, tune cache scope or image size without changing the Docker contract.

## After that

1. Add more integration tests for adjustment and fractional-quantity edge cases.
2. Decide whether deadlock retry policy and migration tooling belong in the extracted adapter package or stay application-owned.
3. Extract example Laravel / Symfony adapter packages if the package is going to be consumed immediately.

## Risks

- Local environment cannot install PHPUnit because `ext-dom` and `ext-xmlwriter` are missing.
- Hosted GitHub Actions confirmation still needs to be run for the Docker legacy job, the hosted modern jobs and the hosted SQLite-backed reference DB job.
- Weighted average now keeps 6-decimal movement/allocation totals aligned, but the core still uses float arithmetic unless a decimal abstraction is introduced later.
- Move service now blocks transfers beyond unreserved availability, but reservations are still not re-mapped automatically during transfer workflows.
- A source checkout without `composer.lock` still cannot rely on `composer install --no-dev` as a local runtime-only shortcut.
- Legacy Docker build depends on archived Debian mirrors remaining reachable.
- GitHub cache behavior for Docker layers and Composer downloads is still unverified remotely.
- This workspace cannot perform remote validation because GitHub tooling/access is still unavailable here.
- Another local-only CI iteration would not add value until GitHub Actions can be reached from a real checkout.
- The reference DB adapter is intentionally minimal: no migration runner, no deadlock retries and no vendor-specific SQL tuning yet.
- The reference PDO adapter is now confirmed on SQLite, MySQL and PostgreSQL baselines, but higher-concurrency and production-sized workloads still need target-environment validation.
- The root cause behind the latest Average Cost failure was an expectation defect, not a persistence-layer defect.
- Fractional weighted-average consistency is improved for shipment and negative-adjustment paths, but broader quantity normalization still depends on disciplined 6-decimal usage across callers and target databases.
- Host PHP still lacks `ext-pdo_pgsql`, so PostgreSQL verification currently depends on the Docker helper path rather than direct host execution.
- The local MySQL Docker helper verifies one concrete MySQL baseline, but target-environment differences may still reveal vendor-specific issues.
- The local PostgreSQL Docker helper verifies one concrete PostgreSQL baseline, but target-environment differences may still reveal vendor-specific issues.
- The extracted package intentionally depends on runtime classes from `warehouse-core`, so publishing two packages will require version discipline across both manifests.
- Schema-copy drift remains a real risk if root schema changes are not mirrored into package resources in the same commit.
- Real publication still needs a git-connected environment with access to both the monorepo remote and the adapter split remote.
- `git subtree split` now validates locally through the dry-run tooling, but remote reachability and committed release-candidate discipline still need to be confirmed in the actual release environment.

## Deferred by design

- Framework-specific adapters
- Real database implementation
- External HTTP or scanner integrations beyond stubs
- Reservation-to-shipment consumption orchestration

## Good next task without rescanning

- Open `docs/HANDOFF.md`, inspect `docs/RELEASE_EXECUTION.md`, then resume from the release blockers already identified instead of re-deriving them.
