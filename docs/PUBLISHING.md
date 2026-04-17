# Publishing

## Public Packagist

1. Publish `storepackage/warehouse-core` from the monorepo root repository.
2. Publish `storepackage/warehouse-pdo-adapter` from a dedicated split repository generated with `git subtree split`.
3. Submit both repository URLs to Packagist.
4. Use plain semver tags in each published repository.
5. Enable auto-update hooks if desired.

## Private Packagist

1. Add the monorepo root repository for `storepackage/warehouse-core`.
2. Add the split adapter repository for `storepackage/warehouse-pdo-adapter`.
3. Define access control and mirrored dist/source settings for both.
4. Sync tags for both published repositories.

## Ready now

- Composer package metadata
- PSR-4 autoload layout
- README, changelog, license, CI workflow, examples and project memory docs
- No runtime dependency beyond PHP itself
- Split test strategy for legacy and modern PHP lines
- Dependency-free `composer verify` fallback for repository-level sanity checks
- In-repo extraction workspace for a production-focused PDO adapter package at `packages/warehouse-pdo-adapter/`
- Frozen ownership model:
  - `storepackage/warehouse-core` publishes runtime, reference adapter and verification guidance
  - `storepackage/warehouse-pdo-adapter` publishes production-facing PDO bootstrap/schema/docs on top of `warehouse-core`
- Documented versioning discipline:
  - both packages use semantic versioning
  - major/minor lines stay aligned while they share this repository
  - current adapter compatibility line is `warehouse-core ^0.1`
- Documented schema-sync discipline:
  - root MySQL/PostgreSQL schemas are source of truth
  - package schema files are controlled copies updated in the same commit
- Chosen split/publication strategy:
  - `warehouse-core` publishes from the root repository
  - `warehouse-pdo-adapter` publishes from a subtree-split mirror repository

## Not ready yet

- Confirmed test execution on PHP 5.6 / 7.4 / 8.1
- Real tag-based release from VCS
- Packagist / Private Packagist registration exercise
- Release choreography for two packages from one repository
- Actual execution from a real git checkout with access to both remotes
- Clean release worktree with the adapter package present in committed history

## Release checklist

- Run tests for supported PHP versions
- Ensure `test-legacy`, `test-modern` and `runtime-smoke` CI jobs are green
- Confirm versioning and tag strategy for `warehouse-core` and `warehouse-pdo-adapter`
- Confirm that package schema copies match the root source-of-truth schemas
- Follow [docs/RELEASE_EXECUTION.md](docs/RELEASE_EXECUTION.md) for subtree split, tag creation and push order
- Confirm `adapter-remote` exists and that remotes are reachable before attempting the adapter release
- Review README, architecture and worklog
- Update changelog
- Create and push git tag
- Confirm Packagist or Private Packagist sync
- Verify consuming app installation from Composer
