# Compatibility Matrix

## Versioning strategy

- `storepackage/warehouse-core` follows semantic versioning for domain/application contracts and the supported reference PDO runtime.
- `storepackage/warehouse-pdo-adapter` follows semantic versioning for packaging/bootstrap/schema-copy concerns.
- While both packages live in the same repository and boundary path A remains frozen, the safe policy is:
  - major/minor compatibility lines stay aligned
  - patch releases may diverge when they do not expand or narrow the required core line

## Current compatibility rule

- `warehouse-pdo-adapter 0.1.x` requires `warehouse-core ^0.1`

## Practical release discipline

1. If runtime behavior or reference schema changes in `warehouse-core`, release `warehouse-core` first.
2. If the adapter package needs updated bootstrap docs, schema copies or config defaults for that core line, release `warehouse-pdo-adapter` in the same major/minor line.
3. If only package-local docs or packaging notes change, `warehouse-pdo-adapter` may take an independent patch release without forcing a new core release.

## What would force a new compatibility line

- runtime constructor/signature changes relied on by `PdoAdapterFactory`
- schema contract changes that require updated package schema copies
- package bootstrap changes that intentionally require a newer core line
