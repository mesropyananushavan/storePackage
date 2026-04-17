# Testing

## Covered now

- `tests/Unit/FifoValuationStrategyTest.php`
- `tests/Unit/LifoValuationStrategyTest.php`
- `tests/Unit/AverageCostValuationStrategyTest.php`
- `tests/Unit/InventoryWorkflowTest.php`

## Covered scenarios

- FIFO picks oldest lots first
- LIFO picks newest lots first
- Average cost weighted calculation
- Partial depletion across multiple lots
- Insufficient stock handling
- Reservation effect on availability
- Shipment allocation persistence
- Average valuation reproducibility
- Audit persistence of valuation method
- Move plus adjustment flow smoke coverage in unit tests

## Not yet covered well

- Reservation-aware transfer policy
- Concurrent update behavior for DB-backed repositories
- Multi-currency validation edge cases
- Decimal precision edge cases with fractional quantities

## Compatibility strategy

- Runtime core stays PHP 5.6-compatible.
- Dev dependency uses `phpunit/phpunit:^5.7 || ^9.6` so Composer resolves PHPUnit 5.7 on PHP 5.6 and PHPUnit 9.6 on PHP 7.4 / 8.1.
- Tests will use a compatibility base class to support both legacy and namespaced PHPUnit APIs.
- `tests/bootstrap.php` can autoload from Composer or directly from the repository tree.
- Local verification now uses `composer verify`, which runs syntax lint and custom runtime smoke-checks without PHPUnit.

## Execution paths

### Legacy test path

- PHP 5.6
- Composer 2.2 LTS
- PHPUnit 5.7
- Required dev extensions: `dom`, `json`, `libxml`, `mbstring`, `tokenizer`, `xmlwriter`
- Preferred execution mode: Docker
- Local command: `bash tools/run-legacy-tests-docker.sh`
- Composer shortcut: `composer test:legacy:docker`
- Faster repeat local command: `WAREHOUSE_LEGACY_SKIP_BUILD=1 bash tools/run-legacy-tests-docker.sh`
- CI job: `test-legacy`
- CI optimization: Docker Buildx layer cache + cached `.cache/composer-legacy`
- Confirmed locally: full Docker run and repeat skip-build run

### Modern test path

- PHP 7.4 and 8.1
- Composer 2.7
- PHPUnit 9.6
- Required dev extensions: `dom`, `json`, `libxml`, `mbstring`, `tokenizer`, `xmlwriter`
- CI job: `test-modern`
- CI optimization: Composer download cache keyed by OS, PHP version and `composer.json`

### Fallback path

- `composer verify`
- `composer test:smoke`
- No PHPUnit dependency
- Intended for local sanity-checking when PHPUnit extensions are unavailable

## Known limitations

- Legacy PHPUnit execution is now confirmed in the Docker path with PHP 5.6.40 and PHPUnit 5.7.27.
- GitHub Actions matrix still needs live confirmation for the Docker legacy job and the hosted modern jobs.
- `composer install --no-dev` from a source checkout without `composer.lock` is not a reliable workaround, because Composer still resolves dev constraints before skipping package installation.
- Legacy Docker builds still rely on archived Debian package mirrors, so image build stability depends on archive availability.
- GitHub-side effectiveness of Buildx cache and Composer caches still needs a real remote run to confirm.
- This workspace cannot trigger or inspect GitHub Actions because it is not a git checkout and has no `gh` CLI.
- Remote validation was re-checked in the current iteration and is still blocked by workspace limitations, not by any newly observed workflow issue.
