# Testing and Health Checks

This base CMS has two project health levels.

## Basic health

Run before finishing normal backend, frontend, migration, or CMS module work:

```bash
composer health
```

The basic check validates the Composer manifest, clears Laravel config cache, checks that the current database is migrated, verifies CMS models/config/navigation/module registry against the current schema, scans live code and seeders for removed module residue, and runs the focused admin/database convention tests.

## Extended health

Run before finishing major features, large refactors, database cleanups, release branches, and at least monthly:

```bash
composer health:extended
```

The extended check includes the basic check, the full Laravel test suite, the production Vite build, and an isolated `migrate:fresh --seed` pass on a temporary SQLite database followed by the same schema health checks. It must never run `migrate:fresh` against the local project database.

## Procedure

- Treat `composer health` as the minimum handoff gate for focused changes.
- Treat `composer health:extended` as the release and major-feature gate.
- Add or update feature tests when changing route behavior, admin workflows, migrations, importers, authorization, localization, public rendering, or API output.
- Keep health checks green before claiming a task is complete. If a check cannot be run, record why and what risk remains.
- Run `vendor/bin/pint --test` as a style audit when touching broad PHP areas. Full Pint is not yet a hard health gate because the current repository has formatting baseline debt.
