# Release Checklist

## Before releasing `warehouse-core`

1. Commit the intended release candidate.
2. Run `composer release:rehearse`.
3. Confirm runtime and reference-schema changes are intentional.
4. Update root docs if adapter ownership, schema behavior or verification guidance changed.
5. If MySQL/PostgreSQL root schemas changed, update the package schema copies in the same commit.

## Before releasing `warehouse-pdo-adapter`

1. Commit the intended release candidate.
2. Run `composer release:rehearse`.
3. In the release environment, run `php tools/run-release-rehearsal.php --check-remotes`.
4. Confirm `composer.json` still points to the intended compatible core line.
5. Review `docs/COMPATIBILITY.md`.
6. Review `docs/SCHEMA_SYNC.md`.
7. Confirm package schema copies still match the root source-of-truth files.
8. Update package README or docs if bootstrap, operations or publishing guidance changed.
9. Create a monorepo bookkeeping tag such as `pdo-adapter-v0.1.0`.
10. Create a subtree split branch from `packages/warehouse-pdo-adapter/`.
11. Tag the split commit with the plain semver tag that the published adapter repo will expose.
12. Confirm the split branch was produced from committed package history rather than only from uncommitted workspace files.

## Release note expectations

- Call out the compatible `warehouse-core` line.
- Mention schema-copy updates explicitly.
- Mention whether the release is:
  - packaging/docs only
  - bootstrap/config change
  - compatibility-line update
