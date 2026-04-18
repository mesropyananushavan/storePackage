# Package Publishing Notes

## Role of this package

- `storepackage/warehouse-pdo-adapter` is intended to publish production-facing PDO bootstrap and schema guidance.
- It depends on `storepackage/warehouse-core`, which continues to own the verified PDO runtime classes.

## Chosen publication path

- Publish this package from a dedicated repository fed by:
  - `git subtree split --prefix=packages/warehouse-pdo-adapter`
- Do not publish this package directly from the monorepo root.
- Do not attempt the split release before this package exists in committed history and the split remote is configured.

## Versioning policy

- Publish against an explicit compatible `warehouse-core` line.
- Current rule: `warehouse-pdo-adapter 0.1.x` requires `warehouse-core ^0.1`.
- Keep major/minor lines aligned while both packages remain in the same repository.
- Allow independent patch releases only when the change is package-local and does not widen or narrow the supported core line.

## What should be published from here

- `composer.json`
- `src/Config/PdoAdapterConfig.php`
- `src/Factory/PdoAdapterFactory.php`
- `resources/schema/*.sql`
- package-local docs

## What should not be moved here implicitly

- `src/Infrastructure/Pdo/*`
- root smoke scripts
- Docker verification helpers

Those remain part of `warehouse-core` until a future iteration explicitly opens a deprecation-based runtime move.

## Publishing caveat

- A real standalone package release from this shared repository still needs a split-package publication mechanism, such as subtree split or equivalent packaging automation.
- That operational step is now specified as `git subtree split`; it is still manual in this iteration.
- Run `composer release:rehearse` before any adapter tag/split work, and use `php tools/run-release-rehearsal.php --check-remotes` from the actual release environment.

## Release discipline

1. Tag `warehouse-core` first when runtime-facing behavior changes.
2. Keep the adapter package constraint aligned with the minimum verified core version.
3. Treat schema copies in this package as versioned bootstrap assets that should track the corresponding verified root schema.
4. Treat `database/schema/mysql.sql` and `database/schema/postgresql.sql` as source of truth and sync package copies in the same commit.
5. Use `docs/COMPATIBILITY.md`, `docs/SCHEMA_SYNC.md` and `docs/RELEASES.md` as the package-local release checklist.
