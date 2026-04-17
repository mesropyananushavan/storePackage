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
