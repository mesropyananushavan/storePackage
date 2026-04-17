# Handoff

## Continue from here

- Start with `docs/PROJECT_STATE.md`, `docs/NEXT_STEPS.md`, `docs/DECISIONS_LOG.md`.
- Then inspect `docs/TESTING.md` and `docs/PUBLISHING.md` before doing any wider scan.
- Next run for CI validation must happen from a real git checkout with GitHub access.
- Do not spend another iteration on local CI hardening unless a real GitHub Actions run exposes a pipeline problem.
- Remote validation was re-checked again and remains blocked only by missing `.git` metadata and `gh`.

## First files to inspect next

- `docs/PROJECT_STATE.md`
- `docs/NEXT_STEPS.md`
- `docs/TESTING.md`
- `.github/workflows/ci.yml`
- `composer.json`
- `docker/legacy/php56/Dockerfile`
- `tools/run-legacy-tests-docker.sh`

## Most important open work

- Run the split CI matrix in GitHub Actions from a real git checkout
- Confirm the Docker-based legacy CI job on GitHub Actions
- Confirm cache reuse behavior in the legacy Docker job and the modern Composer jobs
- If CI is green, move directly to the reference DB adapter step

## Sensitive areas

- PHP 5.6 syntax compatibility
- Valuation reproducibility for average cost
- Transfer policy and cost provenance
- Reservation impact on availability versus physical stock
- Test strategy split between PHPUnit and dependency-free smoke verification
- The Docker legacy environment depends on archived Debian mirrors and should stay minimal
- CI hardening should stay understandable; avoid adding ornamental jobs or exotic caching layers
- The next CI validation step requires actual repository metadata and GitHub permissions

## Do not break

- Framework-agnostic boundaries
- Packagist-ready metadata
- Project memory workflow
- Snapshot persistence for average cost operations
- The separate legacy/modern CI strategy
- The verified Docker-based PHP 5.6 fallback path
- The remote-validation checklist in `README.md`
