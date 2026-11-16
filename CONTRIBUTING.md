# Contributing to LightLMS

Thanks for your interest in improving LightLMS. This document explains how to get a
development environment running and what we expect from a pull request.

> Documentation is in English. Indonesian translations of the interface are welcome —
> see [Localisation](#localisation).

## Code of conduct

Be respectful and constructive. Assume good faith. Harassment of any kind is not
acceptable in issues, pull requests, or discussions.

## Getting set up

You need PHP 8.3+, Composer 2, and Node.js 22+ with pnpm 12 (Corepack ships with Node.js).

```bash
git clone https://github.com/lendradev/lightlms.git
cd lightlms
corepack enable pnpm
composer install
pnpm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
pnpm run build
php artisan serve
```

For hot reload, run `pnpm run dev` in a second terminal.

### Working in Docker

```bash
cp .env.docker.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
```

The image build keeps one shared package cache (`lightlms-pkg-cache`) for pnpm and Composer, so
repeated builds only re-run the layers whose sources changed.

If you prefer a local toolchain inside containers, `docker/dev.sh start` creates the same shared
cache volume and mounts the working tree read-only:

```bash
bash docker/dev.sh start
bash docker/dev.sh stop
```

### Demo data

`docker/demo.sh` prepares a working demo environment in a build container:

```bash
bash docker/demo.sh sbx-lightlms-build
```

The demo database lives on a named volume (`sbx-lightlms-db`), so it survives container rebuilds
and source syncs — there is no need to migrate and re-seed for every change. The script only
seeds when the database is still empty, and prints what it did.

## Before you open a pull request

Run the full check suite:

```bash
composer run lint          # Laravel Pint (PHP style)
php artisan test           # PHP test suite
pnpm run typecheck          # TypeScript
pnpm run lint               # ESLint
pnpm run format:check       # Prettier
```

All of them must pass.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(ui): add course outline sidebar
fix(api): correct pagination links on the catalogue
docs(readme): add local development steps
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`,
`revert`. Keep the subject under 72 characters and in the imperative mood.

## Pull requests

1. Open an issue first for anything larger than a bug fix, so the approach can be agreed
   on before you write code.
2. Create a branch from `main`: `feat/short-description`, `fix/short-description`.
3. Keep the diff focused. Unrelated refactors make review harder.
4. Add or update tests for any behaviour change. A bug fix without a regression test is
   not finished.
5. Describe what changed and why in the pull request body.
6. Update `README.md` and the docs if you changed setup, configuration, or user-facing
   behaviour.

## Code style

- **PHP**: Laravel Pint is the single source of truth. Run `composer run lint`.
- **TypeScript**: Prettier for formatting, ESLint for correctness. Run `pnpm run format`
  and `pnpm run lint`.
- **Design tokens**: never hardcode colours, spacing, or radii in components. Add or
  change tokens in `resources/css/app.css` under `@theme`, then use the utility classes.
- **Comments**: comment the *why*, never the *what*. The code should explain itself.

## Architecture conventions

- Page components live in `resources/js/pages/` and map to Inertia page names
  (`Courses/Index.tsx` → `inertia('Courses/Index')`).
- Shared React components live in `resources/js/components/`.
- Shared TypeScript types live in `resources/js/types/`.
- Business rules belong in models or dedicated action classes, not in controllers or page
  components.
- Routes are defined in `routes/web.php`; keep the controller thin.

## Localisation

English is the primary language. Indonesian (`id`) is the secondary locale and is already
registered in `config/app.php`. To add a locale:

1. Add it to the `locales` array in `config/app.php`.
2. Add the translation files under `lang/<locale>/`.
3. Use `__()` in PHP and `t()` in React components — never hardcode user-facing strings.

## Reporting security issues

Do not open a public issue. Report privately to the maintainers instead, including a
description, affected versions, and reproduction steps. See the Security section of the
README for operational guidance.
