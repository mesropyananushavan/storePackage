# Operations Notes

## Connection defaults

- Use `PDO::ERRMODE_EXCEPTION`.
- Set vendor-specific session defaults explicitly at bootstrap time.
- Keep credentials and DSN outside source control.

## Locking policy

- Current reference runtime uses `SELECT ... FOR UPDATE` only inside active transactions.
- MySQL baseline assumes `InnoDB`.
- PostgreSQL baseline assumes ordinary row locks under `read committed`.
- SQLite remains a reference-only path and not a production locking model.

## Retry and deadlock policy

- `PdoAdapterConfig` already carries placeholders for retry attempts and retry delay.
- No retry loop is implemented yet in this extraction step.
- The consuming application or a later package iteration should decide:
  - whether deadlock retries happen inside the transaction manager
  - how many retries are acceptable
  - which SQLSTATEs are treated as retryable

## Deployment-facing defaults

- Keep schema ownership in the application migration layer.
- Record transaction isolation and lock-timeout defaults in deployment docs.
- Validate decimal precision and timestamp conventions against the target database before go-live.
