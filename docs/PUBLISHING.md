# Publishing

## Public Packagist

1. Push repository to a public VCS host.
2. Ensure `composer.json` metadata is complete.
3. Tag a release, for example `v0.1.0`.
4. Submit repository URL to Packagist.
5. Enable auto-update hook if desired.

## Private Packagist

1. Push repository to the VCS host connected to Private Packagist.
2. Add repository in Private Packagist.
3. Define access control and mirrored dist/source settings.
4. Sync tags for releases.

## Ready now

- Composer package metadata
- PSR-4 autoload layout
- README, changelog, license, CI workflow, examples and project memory docs
- No runtime dependency beyond PHP itself
- Split test strategy for legacy and modern PHP lines
- Dependency-free `composer verify` fallback for repository-level sanity checks

## Not ready yet

- Confirmed test execution on PHP 5.6 / 7.4 / 8.1
- Real tag-based release from VCS
- Packagist / Private Packagist registration exercise

## Release checklist

- Run tests for supported PHP versions
- Ensure `test-legacy`, `test-modern` and `runtime-smoke` CI jobs are green
- Review README, architecture and worklog
- Update changelog
- Create and push git tag
- Confirm Packagist or Private Packagist sync
- Verify consuming app installation from Composer
