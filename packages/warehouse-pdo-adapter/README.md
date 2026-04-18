# storepackage/warehouse-pdo-adapter

`storepackage/warehouse-pdo-adapter` is the production-facing PDO adapter packaging layer built on top of `storepackage/warehouse-core`.

## Status

- Boundary is frozen on the safe path:
  - runtime classes stay in `storepackage/warehouse-core`
  - this package owns production-facing bootstrap, schema copies and operations docs
- This package is released from an in-repository subtree path rather than directly from the monorepo root.

## What is included here

- `src/Config/PdoAdapterConfig.php`
- `src/Factory/PdoAdapterFactory.php`
- `resources/schema/mysql.sql`
- `resources/schema/postgresql.sql`
- `docs/BOUNDARIES.md`
- `docs/COMPATIBILITY.md`
- `docs/MIGRATIONS.md`
- `docs/OPERATIONS.md`
- `docs/PUBLISHING.md`
- `docs/RELEASES.md`
- `docs/SCHEMA_SYNC.md`

## Ownership

- Runtime ownership: `storepackage/warehouse-core`
- Reference verification ownership: `storepackage/warehouse-core`
- Production-facing packaging ownership: `storepackage/warehouse-pdo-adapter`
- Schema-copy ownership for application bootstrap/reference migrations: `storepackage/warehouse-pdo-adapter`

The default CI gate for runtime confidence also stays with `warehouse-core`, including the hosted SQLite-backed reference PDO adapter verification path.

## Versioning and compatibility

- `warehouse-core` and `warehouse-pdo-adapter` use semantic versioning.
- While both packages share this repository and boundary path A stays frozen, the safe rule is aligned major/minor lines.
- Current compatibility line:
  - `warehouse-pdo-adapter 0.1.x` requires `warehouse-core ^0.1`
- Patch releases may diverge when they only adjust package-local docs, schema-copy delivery or bootstrap guidance.

See:

- `docs/COMPATIBILITY.md`
- `docs/SCHEMA_SYNC.md`
- `docs/RELEASES.md`
- `docs/PUBLISHING.md`

## What is intentionally not duplicated

- The verified repository implementations from `src/Infrastructure/Pdo/`
- The smoke scripts and Docker verification helpers
- Vendor-specific migration runners or retry logic

This is deliberate, not temporary cleanup debt. The current package boundary is meant to stay stable until there is an explicit need for a deprecation-based runtime move.

## Schema delivery

- Root source of truth:
  - `database/schema/mysql.sql`
  - `database/schema/postgresql.sql`
- Package delivery copies:
  - `resources/schema/mysql.sql`
  - `resources/schema/postgresql.sql`

Schema copies must be updated in the same commit whenever the corresponding root schema changes.

## Publication model

- `storepackage/warehouse-core` publishes from the monorepo root repository.
- `storepackage/warehouse-pdo-adapter` publishes from a subtree-split mirror repository built from `packages/warehouse-pdo-adapter/`.
- Monorepo bookkeeping tags for this package use `pdo-adapter-vX.Y.Z`.
- The published adapter repository uses plain semver tags like `v0.1.0`.
- The subtree release requires:
  - this package directory to be committed in monorepo history
  - an `adapter-remote` to exist
  - reachable remotes during the release run

Use the root preflight before adapter release work:

- `composer release:dry-run`
- `composer release:rehearse`
- `php tools/run-release-rehearsal.php --check-remotes` from the release environment

## Minimal bootstrap

```php
<?php

use StorePackage\WarehousePdoAdapter\Config\PdoAdapterConfig;
use StorePackage\WarehousePdoAdapter\Factory\PdoAdapterFactory;

$config = new PdoAdapterConfig(
    'mysql:host=127.0.0.1;port=3306;dbname=warehouse',
    'warehouse',
    'warehouse',
    array(),
    array(),
    15,
    0,
    0,
    array('SET NAMES utf8mb4')
);

$factory = new PdoAdapterFactory();
$services = $factory->create($config);
```

The returned array contains the PDO handle plus repository and transaction-manager instances keyed for straightforward application wiring.
