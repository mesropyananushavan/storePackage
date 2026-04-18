# Changelog

## [Unreleased]

## [0.1.1] - 2026-04-18

- Hardened transfer flow so reserved stock cannot be moved out of the source location.
- Tightened weighted-average rounding consistency for fractional quantities, costs and snapshot-based valuation.
- Expanded unit and PDO-backed precision coverage for fractional receive, ship and adjustment scenarios.
- Added a hosted SQLite-backed PDO reference adapter job to GitHub Actions.
- Added reproducible release preflight and clean release rehearsal tooling:
  - `composer release:dry-run`
  - `composer release:rehearse`
- Aligned subtree-split publishing docs, schema-copy discipline and adapter packaging guidance.

- Initial production-oriented warehouse core package.
- Added legacy-compatible domain model, application services and in-memory infrastructure.
- Added FIFO, LIFO and weighted average valuation strategies.
- Added project memory and publishing documentation.
