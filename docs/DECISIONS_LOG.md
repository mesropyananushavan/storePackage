# Decisions Log

## 2026-04-17 / Iteration 1

- Decision: keep the core PHP 5.6-compatible and avoid modern syntax.
- Reason: package must run in legacy environments and remain reusable.
- Consequences: PHPDoc-heavy contracts, no typed properties, no modern language sugar.

## 2026-04-17 / Iteration 1

- Decision: keep valuation behind a strategy interface.
- Reason: FIFO, LIFO and weighted average must be swappable and testable.
- Consequences: application services depend on a resolver instead of branching on method logic inline.

## 2026-04-17 / Iteration 1

- Decision: separate framework integration from core.
- Reason: core must stay publishable and framework-agnostic.
- Consequences: only stubs and extension guidance are included here.

## 2026-04-17 / Iteration 2

- Decision: use a split transfer policy for partial moves and keep lot identity for full-lot moves.
- Reason: preserves provenance and avoids losing FIFO/LIFO aging metadata.
- Consequences: transfer logic can create derived lots with `parentLotId`.

## 2026-04-17 / Iteration 2

- Decision: persist weighted average snapshots for shipment and negative adjustment operations.
- Reason: average costing must be reproducible and audit-friendly.
- Consequences: snapshot repository is part of the core contracts and infrastructure.

## 2026-04-17 / Iteration 2

- Decision: use dual PHPUnit version constraints instead of one modern-only dev stack.
- Reason: PHP 5.6 and PHP 8.1 cannot realistically share a single PHPUnit major.
- Consequences: tests rely on a compatibility base class and still need matrix verification in a proper environment.

## 2026-04-17 / Iteration 2

- Decision: use float arithmetic with 6-decimal rounding in the core.
- Reason: keeps runtime dependency-free compatibility with PHP 5.6.
- Consequences: high-precision financial use cases may later need a decimal abstraction or adapter-level math service.

## 2026-04-17 / Iteration 3

- Decision: keep one runtime composer manifest, but split test execution into legacy and modern paths operationally.
- Reason: PHP 5.6 and PHP 8.1 need different PHPUnit majors, but duplicating the package manifest would add avoidable maintenance overhead.
- Consequences: CI has separate jobs, while the root `require-dev` keeps a platform-resolved PHPUnit constraint.

## 2026-04-17 / Iteration 3

- Decision: add a dependency-free `composer verify` path alongside PHPUnit.
- Reason: local environments may lack `ext-dom` or `ext-xmlwriter`, and library repositories without a lock file cannot always use `composer install --no-dev` as a workaround.
- Consequences: lint and smoke scripts are first-class verification tools, but they do not replace the need for real PHPUnit runs in the target matrix.

## 2026-04-17 / Iteration 4

- Decision: run the PHP 5.6 CI path through Docker by default instead of relying on hosted PHP 5.6 setup.
- Reason: old hosted runtimes are the least reliable part of the matrix, while the container contract can be versioned with the repository.
- Consequences: the legacy job is more reproducible, but Docker build time and archived Debian mirror availability become explicit operational constraints.

## 2026-04-17 / Iteration 4

- Decision: fix interface/implementation signature mismatches that surfaced under real PHP 5.6 execution.
- Reason: PHP 5.6 enforces interface compatibility at runtime, and the package claims real 5.6 support.
- Consequences: a few infrastructure classes now mirror interface type hints exactly; business logic remained unchanged.

## 2026-04-17 / Iteration 5

- Decision: keep CI caching pragmatic: Buildx cache for the legacy Docker image and Composer download caches for hosted modern jobs.
- Reason: this gives most of the available speedup without turning the workflow into a bespoke release pipeline.
- Consequences: the workflow is faster and clearer on paper, but real cache effectiveness still needs remote confirmation on GitHub Actions.

## 2026-04-17 / Iteration 9

- Decision: ship the reference database adapter as plain PDO infrastructure inside the core package instead of introducing a DBAL or framework integration layer.
- Reason: the package needs a readable persistence reference that stays framework-agnostic and PHP 5.6-compatible.
- Consequences: consumers get a working baseline adapter and schema, but production teams still need vendor-specific hardening, migrations and retry policies.

## 2026-04-17 / Iteration 9

- Decision: store stock-movement metadata as JSON text and cost allocations in a separate table keyed by `operation_id`.
- Reason: this mirrors the existing audit model without changing service contracts and stays portable across common PDO drivers.
- Consequences: the adapter remains simple and auditable, but JSON validation and query ergonomics are intentionally limited in the reference implementation.

## 2026-04-17 / Iteration 17

- Decision: start production-focused PDO adapter extraction as an in-repository package workspace before moving runtime classes.
- Reason: the reference PDO runtime is already verified on SQLite, MySQL and PostgreSQL, so the lowest-risk next step is to separate packaging, schema ownership and operational concerns without destabilizing the proven runtime.
- Consequences: `packages/warehouse-pdo-adapter/` now owns config/factory scaffolding and production-facing docs, while `src/Infrastructure/Pdo/` remains the active verified runtime until the packaging boundary is frozen.

## 2026-04-17 / Iteration 18

- Decision: freeze the package boundary on path A and keep `src/Infrastructure/Pdo/` in `warehouse-core` as supported runtime.
- Reason: moving the verified runtime into a second package now would introduce avoidable breakage risk and deprecation overhead before packaging and publishing roles are even fully stabilized.
- Consequences: `warehouse-pdo-adapter` becomes a production-facing bootstrap/schema/docs layer on top of `warehouse-core`, and any future runtime move is deferred to a dedicated deprecation iteration.

## 2026-04-17 / Iteration 19

- Decision: keep package version lines compatibility-aligned and treat root vendor-specific schema files as source of truth.
- Reason: the extracted adapter package depends on the core runtime and ships controlled schema copies, so publishable discipline needs explicit compatibility and sync rules before any real release attempt.
- Consequences: `warehouse-pdo-adapter 0.1.x` now targets `warehouse-core ^0.1`, package schema copies must be updated in the same commit as root MySQL/PostgreSQL schema changes, and package-local compatibility/sync/release docs are now required reading for publication.

## 2026-04-17 / Iteration 20

- Decision: use `git subtree split` as the monorepo publishing strategy for `storepackage/warehouse-pdo-adapter`, while `storepackage/warehouse-core` continues to publish directly from the root repository.
- Reason: this is the most practical low-risk path for the current structure because the core package already matches the repository root and the adapter package already lives under a clean subtree prefix.
- Consequences: release choreography now depends on a second remote repository for the adapter package, monorepo adapter bookkeeping tags use a package-scoped format, and split/push steps are documented as the required manual path until later automation is introduced.

## 2026-04-17 / Iteration 21

- Decision: treat committed package history and configured adapter remote as hard prerequisites for subtree-based release dry-runs.
- Reason: the first real dry-run showed that `git subtree split` cannot validate an uncommitted package subtree and that documenting the split flow is not enough unless the remote layout also exists.
- Consequences: release docs now explicitly require a clean/committed worktree, `adapter-remote`, and reachable remotes before the first meaningful adapter release attempt.

## 2026-04-18 / Iteration 22

- Decision: block stock transfers that would consume source inventory already reserved at that same warehouse/location scope.
- Reason: transfering against on-hand only could push source availability negative and break the reservation contract even when shipment and adjustment flows already respected reservations.
- Consequences: `MoveStockService` now accepts an optional reservation repository for availability-aware guards, existing constructor calls remain valid, and transfer coverage now includes both in-memory and PDO-backed rejection paths.

## 2026-04-18 / Iteration 23

- Decision: keep weighted-average arithmetic float-based, but normalize request/basis totals to 6 decimals and assign the final rounding remainder to the last allocation line.
- Reason: the existing average-cost path could drift by `0.000001` between summed allocation totals and the rounded movement total during fractional operations, which weakens audit reproducibility even if the public API stays the same.
- Consequences: shipment and negative-adjustment average-cost operations now keep allocation totals, movement totals and snapshots internally aligned on both in-memory and PDO-backed paths without introducing a new decimal dependency.

## 2026-04-18 / Iteration 24

- Decision: make the SQLite-backed reference PDO adapter path part of the default GitHub Actions gate.
- Reason: local release confidence already depended on `composer test:db-smoke` and `PdoReferenceAdapterTest`, but the hosted CI matrix did not exercise the reference persistence path at all.
- Consequences: the repository now expects a green `test-db-reference` job alongside legacy, modern and runtime-smoke jobs before calling the release matrix fully confirmed.

## 2026-04-18 / Iteration 25

- Decision: codify release preflight as a repository tool instead of relying on an informal checklist for subtree/publish dry-runs.
- Reason: release docs had already drifted from the actual git state, and the highest-risk publication mistakes were procedural: dirty worktree, unsynced schema copies, missing subtree validation and inconsistent assumptions about remotes.
- Consequences: `composer release:dry-run` now validates root/package manifests, schema-copy sync, remote configuration and local subtree-split reproducibility; package schema copies were also tightened to match the root source-of-truth files exactly so the dry-run can fail on real drift instead of comment noise.

## 2026-04-18 / Iteration 26

- Decision: make clean release rehearsal a first-class workflow by validating committed `HEAD` inside a temporary clean worktree.
- Reason: strict preflight on the live workspace is useful, but it still leaves ambiguity when the current tree is dirty; before any real tag/push step, the team needs one deterministic command that answers whether the committed release candidate itself is publish-ready.
- Consequences: `composer release:rehearse` now provides clean-checkout confirmation without touching the current workspace, and release docs now use that rehearsal as the canonical pre-tag path before optional remote reachability checks.
