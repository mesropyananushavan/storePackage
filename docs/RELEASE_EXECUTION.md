# Release Execution Plan

## Chosen monorepo publishing strategy

Use `git subtree split` as the primary publishing mechanism.

Why this path:

- `warehouse-core` already lives at the repository root, so it does not need a split step.
- `warehouse-pdo-adapter` lives under `packages/warehouse-pdo-adapter/`, which maps cleanly to a subtree split.
- `git subtree split` uses standard Git only; it does not add an extra binary or service dependency like `splitsh-lite`.
- This is the lowest-risk path for the current project state.

## Repository roles

- Monorepo root repository:
  - source of truth for development
  - source of truth for `storepackage/warehouse-core`
  - source of truth for schema files under `database/schema/`
- Split adapter repository:
  - publication target for `storepackage/warehouse-pdo-adapter`
  - built from `packages/warehouse-pdo-adapter/` via subtree split

## Preconditions

Before any real release or meaningful dry-run:

1. The checkout must be a real git worktree.
2. The worktree should be clean, or at minimum the files intended for release must already be committed.
3. `origin` must be reachable.
4. A second remote for the adapter package must exist, for example `adapter-remote`.
5. `packages/warehouse-pdo-adapter/` must be present in committed history, otherwise `git subtree split` has nothing to cut.

## Reproducible dry-run

Use the preflight script before any tag or push step:

- strict local preflight:
  - `composer release:dry-run`
- clean release rehearsal from committed `HEAD`:
  - `composer release:rehearse`
- release-environment rehearsal with remote reachability checks:
  - `php tools/run-release-rehearsal.php --check-remotes`

The dry-run validates:

- git worktree presence
- clean worktree requirement unless `--allow-dirty` is used intentionally
- strict Composer manifest validation for root and adapter packages
- root/package schema-copy sync
- `origin` and `adapter-remote` configuration
- adapter package presence in committed `HEAD`
- `git subtree split --prefix=packages/warehouse-pdo-adapter HEAD`

`composer release:rehearse` runs the same strict preflight inside a temporary clean `git worktree` created from committed `HEAD`, so it can be used safely even when the current working tree is dirty. This rehearsal intentionally ignores uncommitted workspace changes.

## Tagging policy

- Core release tags in the monorepo root use plain semver:
  - `v0.1.0`
- Adapter bookkeeping tags in the monorepo use package-scoped names:
  - `pdo-adapter-v0.1.0`
- Adapter release tags in the split repository use plain semver:
  - `v0.1.0`

This avoids tag-name collisions in the monorepo while keeping the published adapter repository Packagist-friendly.

## Release choreography

### Release `storepackage/warehouse-core`

1. Confirm root package docs and changelog are ready.
2. Confirm root schema files are current.
3. Confirm CI is green from a real git checkout.
4. Create and push the core tag from the monorepo root:
   - `git tag -a v0.1.0 -m "storepackage/warehouse-core v0.1.0"`
   - `git push origin v0.1.0`

### Release `storepackage/warehouse-pdo-adapter`

1. Confirm `packages/warehouse-pdo-adapter/composer.json` points to the intended compatible core line.
2. Confirm package schema copies match root source-of-truth files.
3. Confirm `packages/warehouse-pdo-adapter/` is committed in `HEAD`.
4. Confirm `adapter-remote` is configured.
5. Create a monorepo bookkeeping tag:
   - `git tag -a pdo-adapter-v0.1.0 -m "storepackage/warehouse-pdo-adapter v0.1.0"`
6. Build a split branch from the package directory:
   - `git subtree split --prefix=packages/warehouse-pdo-adapter -b split/pdo-adapter-v0.1.0`
7. Tag the split commit locally with the adapter semver tag:
   - `git tag -a v0.1.0 split/pdo-adapter-v0.1.0 -m "storepackage/warehouse-pdo-adapter v0.1.0"`
8. Push the split branch and semver tag to the dedicated adapter repository:
   - `git push adapter-remote split/pdo-adapter-v0.1.0:main`
   - `git push adapter-remote v0.1.0`
9. Push the monorepo bookkeeping tag:
   - `git push origin pdo-adapter-v0.1.0`

## Compatibility discipline

- `warehouse-pdo-adapter 0.1.x` requires `warehouse-core ^0.1`
- Release `warehouse-core` first when:
  - runtime constructor/signature behavior changes
  - root MySQL/PostgreSQL schema changes
  - package-local compatibility line needs to move
- Adapter patch releases may happen independently only when the change is package-local.

## Schema-sync gate before adapter release

Before step 3 of the adapter release:

1. Diff `database/schema/mysql.sql` against `packages/warehouse-pdo-adapter/resources/schema/mysql.sql`
2. Diff `database/schema/postgresql.sql` against `packages/warehouse-pdo-adapter/resources/schema/postgresql.sql`
3. Confirm differences are zero or explicitly intended and documented

## What is manual now

- creating and pushing the split branch
- tagging the split commit
- pushing to the adapter mirror repository
- Packagist / Private Packagist registration and sync checks

## Latest dry-run result

- Real git checkout: confirmed
- `origin` configured: confirmed
- `adapter-remote` configured: confirmed
- `packages/warehouse-pdo-adapter/` present in committed `HEAD`: confirmed
- `git subtree split` on `packages/warehouse-pdo-adapter/`: confirmed locally and currently resolves to split commit `325b54f71c0290313e82a524ffe35d30f7d41280`
- strict dry-run still requires a clean worktree; use `composer release:rehearse` for clean-checkout confirmation against committed `HEAD`
- remote reachability from this environment: not confirmed; use `--check-remotes` in a release-ready environment

## What can be automated later

- subtree split and push from CI after tag creation
- schema-copy diff checks
- release note generation
- mirror-branch refresh
