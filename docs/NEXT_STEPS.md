# Next Steps

## Highest priority

1. Run the GitHub Actions matrix from a real git checkout and confirm `test-legacy`, `test-modern` and `runtime-smoke` jobs on real runners.
2. If the Docker-based legacy job is still slow in Actions after Buildx caching, tune cache scope or image size without changing the Docker contract.
3. Add database-backed repository implementations or a reference persistence adapter.

## After that

1. Add integration tests for move and adjustment edge cases.
2. Add explicit concurrency behavior for DB-backed repositories.
3. Extract example Laravel / Symfony adapter packages if the package is going to be consumed immediately.

## Risks

- Local environment cannot install PHPUnit because `ext-dom` and `ext-xmlwriter` are missing.
- Hosted GitHub Actions confirmation still needs to be run for the Docker legacy job and the hosted modern jobs.
- Weighted average uses float arithmetic unless a decimal abstraction is introduced later.
- Move service currently does not re-map reservations during transfer.
- A source checkout without `composer.lock` still cannot rely on `composer install --no-dev` as a local runtime-only shortcut.
- Legacy Docker build depends on archived Debian mirrors remaining reachable.
- GitHub cache behavior for Docker layers and Composer downloads is still unverified remotely.
- This workspace cannot perform remote validation because there is no `.git` repository and no `gh` CLI.
- Another local-only CI iteration would not add value until GitHub Actions can be reached from a real checkout.

## Deferred by design

- Framework-specific adapters
- Real database implementation
- External HTTP or scanner integrations beyond stubs
- Reservation-to-shipment consumption orchestration

## Good next task without rescanning

- Open `docs/HANDOFF.md` from a real git checkout, run the GitHub Actions matrix, then either optimize Docker caching or move on to persistence adapters.
