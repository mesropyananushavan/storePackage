# Release Checklist

## Before releasing `warehouse-core`

1. Confirm runtime and reference-schema changes are intentional.
2. Update root docs if adapter ownership, schema behavior or verification guidance changed.
3. If MySQL/PostgreSQL root schemas changed, update the package schema copies in the same commit.

## Before releasing `warehouse-pdo-adapter`

1. Confirm `composer.json` still points to the intended compatible core line.
2. Review `docs/COMPATIBILITY.md`.
3. Review `docs/SCHEMA_SYNC.md`.
4. Confirm package schema copies still match the root source-of-truth files.
5. Update package README or docs if bootstrap, operations or publishing guidance changed.
6. Create a monorepo bookkeeping tag such as `pdo-adapter-v0.1.0`.
7. Create a subtree split branch from `packages/warehouse-pdo-adapter/`.
8. Tag the split commit with the plain semver tag that the published adapter repo will expose.
9. Confirm the split branch was produced from committed package history rather than only from uncommitted workspace files.

## Release note expectations

- Call out the compatible `warehouse-core` line.
- Mention schema-copy updates explicitly.
- Mention whether the release is:
  - packaging/docs only
  - bootstrap/config change
  - compatibility-line update
