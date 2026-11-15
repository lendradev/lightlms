<div align="center">

# LightLMS

**A modern, self-hosted open-source Learning Management System.**

[![License: MIT](https://img.shields.io/badge/License-MIT-crimson.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-v3-9558B2.svg)](https://inertiajs.com)
[![React](https://img.shields.io/badge/React-19-087EA4.svg)](https://react.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-06B6D4.svg)](https://tailwindcss.com)
[![Docker](https://img.shields.io/badge/Docker-supported-2496ED.svg)](docker-compose.yml)

</div>

---

LightLMS is a learning platform for schools, universities and independent educators: courses,
modules, lessons, assignments and progress tracking, deployed on infrastructure you control.
It is a conventional Laravel application with a React front end served through Inertia.js, so
every part of it is readable, testable and replaceable.

> **Status:** early development. The data model and public catalogue are stable; authentication
> and the instructor authoring experience are the next milestones. See the roadmap below.

---

## Table of contents

- [Why another LMS](#why-another-lms)
- [Feature roadmap](#feature-roadmap)
- [Tech stack](#tech-stack)
- [Quick start](#quick-start)
- [Docker deployment](#docker-deployment)
- [Configuration](#configuration)
- [Project structure](#project-structure)
- [Development workflow](#development-workflow)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)
- [Bahasa Indonesia](#bahasa-indonesia)

---

## Why another LMS

Most self-hosted learning platforms are either heavy institutional monoliths (Moodle, Canvas,
Chamilo) built for a very specific feature set, or young Laravel kits that trade depth for speed
and leave you with an admin panel and no coherent learner experience.

LightLMS takes the middle path:

- **A calm learner interface.** The visual language is deliberately restrained — generous
  whitespace, a single crimson accent, a warm neutral type scale — in the spirit of MIT
  OpenCourseWare. Nothing pulses for attention, because nothing should need to.
- **A conventional stack.** Laravel 13, Inertia.js v3, React 19 and Tailwind CSS v4. No
  bespoke framework, no Vue/React hybrid, no lock-in.
- **One-command deployment.** A multi-stage Dockerfile plus a compose file with Postgres,
  Redis and a queue worker. Any Docker host will do.
- **Readable by default.** Controllers stay thin, business logic lives in models and actions,
  and every page is a plain Inertia page component you can open and understand.

---

## Feature roadmap

| Area | Status |
|---|---|
| Course catalogue (browse, filter, paginate) | Available |
| Course detail with module & lesson outline | Available |
| Data model: courses, modules, lessons, enrollments | Available |
| Docker image + compose stack (Postgres, Redis, worker) | Available |
| Authentication (session, roles: student / instructor / admin) | Planned |
| Instructor authoring: create and edit courses, modules, lessons | Planned |
| Learner dashboard: enrolled courses, progress, continue where you left off | Planned |
| Assignments, submissions and grading | Planned |
| Quizzes with question types and attempt tracking | Planned |
| File uploads (course material, submissions) | Planned |
| Notifications by email and in-app | Planned |
| Certificates of completion | Planned |
| REST API for integrations | Planned |
| Multi-language UI (English primary, Bahasa Indonesia secondary) | Planned |

---

## Tech stack

| Layer | Choice | Notes |
|---|---|---|
| Language | PHP 8.3+ | Tested on 8.5 |
| Framework | Laravel 13 | Conventional MVC, Eloquent, queues, scheduler |
| Front-end bridge | Inertia.js v3 | Server-driven navigation, no separate SPA router |
| UI library | React 19 + TypeScript 5.9 | Functional components, no state library needed |
| Styling | Tailwind CSS v4 | CSS-first `@theme` tokens, no JS config file |
| Build tool | Vite 8 | Laravel plugin + Tailwind plugin |
| Database | PostgreSQL 18 (SQLite for local dev) | Migrations via Eloquent |
| Cache / queue | Redis 8 | Laravel cache, database and queue drivers |
| Deployment | Docker (multi-stage) | nginx + php-fpm in a single runtime image |

---

## Quick start

### Requirements

- PHP 8.3 or newer, with `pdo_sqlite` (or `pdo_pgsql`), `mbstring`, `openssl`, `curl`, `zip`
- Composer 2.x
- Node.js 22+ and npm 10+
- SQLite (bundled) or PostgreSQL

### Install

```bash
git clone https://github.com/lendradev/lightlms.git
cd lightlms

composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate
php artisan db:seed          # optional: demo courses

npm run build
php artisan serve
```

The application is now available at <http://127.0.0.1:8000>.

### Development with hot reload

Run the PHP server and Vite in two terminals:

```bash
php artisan serve
npm run dev
```

---

## Docker deployment

LightLMS ships a production-oriented multi-stage image: Node builds the assets, Composer installs
PHP dependencies, and the runtime layer runs nginx and php-fpm as a non-root user.

### 1. Configure

```bash
cp .env.docker.example .env
```

At minimum, set:

```dotenv
APP_KEY=            # generate with: docker compose run --rm app php artisan key:generate --show
DB_PASSWORD=        # a strong password for the Postgres role
APP_URL=https://lms.example.com
```

### 2. Build and start

```bash
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force
```

The stack exposes the application on port `8000` (loopback by default — put a TLS-terminating
reverse proxy in front of it for production).

### 3. Update an existing deployment

```bash
git pull
docker compose build --pull
docker compose up -d
docker compose exec app php artisan migrate --force
```

### Services

| Service | Role |
|---|---|
| `app` | nginx + php-fpm runtime, migrations on boot |
| `worker` | queue worker for asynchronous jobs |
| `db` | PostgreSQL 18 with a persistent volume |
| `cache` | Redis 8 for cache and queue backing |

### Scaling notes

- `app` is stateless apart from the `storage` volume — scale it horizontally behind a load balancer.
- Session and cache use the database and Redis drivers respectively; switch `SESSION_DRIVER` to
  `redis` when running multiple app replicas.
- `docker compose exec app php artisan queue:work` runs a worker in the foreground for debugging.

---

## Configuration

The most relevant environment variables:

| Variable | Default | Purpose |
|---|---|---|
| `APP_ENV` | `local` | `local` / `production` |
| `APP_DEBUG` | `false` in production | Never enable debug on a public deployment |
| `APP_KEY` | — | Application encryption key, required |
| `APP_URL` | `http://localhost:8000` | Canonical URL |
| `APP_LOCALE` | `en` | Interface language |
| `DB_CONNECTION` | `sqlite` | `pgsql` in Docker deployments |
| `SESSION_DRIVER` | `database` | Use `redis` for multiple replicas |
| `CACHE_STORE` | `database` | `redis` in Docker deployments |
| `QUEUE_CONNECTION` | `database` | Queue backend |
| `REDIS_HOST` | `127.0.0.1` | Redis host (`cache` inside compose) |
| `LIGHTLMS_SEED_DEMO_DATA` | `false` | Seed demo content on first boot |

---

## Project structure

```
lightlms/
├── app/
│   ├── Enums/            # UserRole
│   ├── Http/Middleware/  # HandleInertiaRequests — shared props
│   ├── Models/           # Course, Module, Lesson, User
│   └── Support/          # Locale helpers
├── database/
│   ├── factories/        # Model factories for tests and seeding
│   ├── migrations/       # Schema
│   └── seeders/          # Demo data
├── resources/
│   ├── css/app.css       # Tailwind v4 theme tokens and component classes
│   ├── js/
│   │   ├── components/   # Shared React components
│   │   ├── lib/          # Small utilities
│   │   ├── pages/        # Inertia page components
│   │   └── types/        # Shared TypeScript types
│   └── views/app.blade.php   # Inertia root template
├── routes/web.php
├── docker/               # nginx, php-fpm and entrypoint configuration
├── Dockerfile
└── docker-compose.yml
```

---

## Development workflow

```bash
# Backend
php artisan serve          # dev server
php artisan migrate        # apply migrations
php artisan db:seed       # seed demo content
php artisan tinker         # REPL

# Front-end
npm run dev                # Vite dev server with hot reload
npm run build              # production build into public/build
npm run typecheck          # tsc --noEmit
npm run lint               # ESLint
npm run format             # Prettier

# Code style
composer run lint          # Laravel Pint
```

**Design tokens** live in `resources/css/app.css` under `@theme`. The palette is intentionally
small: a crimson brand scale, a warm neutral ink scale, and a teal accent used only for progress
and completion states. Change the tokens there, not in components.

---

## Testing

```bash
php artisan test           # PHP / Pest-style feature + unit tests
npm run typecheck          # TypeScript
npm run lint               # ESLint
```

Tests use an in-memory SQLite database, so the suite runs without any external service.

---

## Contributing

Contributions are welcome and are credited in the commit history.

1. Open an issue first for anything larger than a bug fix, so the approach can be agreed on.
2. Create a branch from `main` with a descriptive name.
3. Keep commits focused; use [Conventional Commits](https://www.conventionalcommits.org/):
   `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.
4. Make sure `composer run lint`, `npm run typecheck`, `npm run lint` and `php artisan test` pass.
5. Open a pull request describing the change and the reasoning behind it.

Development documentation for contributors lives alongside the source code. Product plans,
research notes and internal decisions are tracked separately in the project workspace and are not
part of the public repository.

---

## Security

If you discover a vulnerability, please report it privately rather than opening a public issue.
Include a description, affected versions and reproduction steps.

Security notes for operators:

- Set `APP_DEBUG=false` and a unique `APP_KEY` in production.
- Terminate TLS in front of the container; the bundled nginx listens on plain HTTP.
- Rotate `DB_PASSWORD` and database credentials periodically.
- Run the image as a non-root user (the default runtime image already does).

---

## License

LightLMS is released under the [MIT License](LICENSE). You may use it commercially, modify it,
and distribute it, provided the copyright notice and permission notice are included.

---

## Bahasa Indonesia

**LightLMS** adalah sistem manajemen pembelajaran (LMS) open-source yang bisa di-hosting sendiri
untuk sekolah, kampus, danWidaguru independen: katalog kursus, modul, pelajaran, tugas, serta
pelacakan progres peserta.

**Tumpukan teknologi:** Laravel 13, Inertia.js v3, React 19, TypeScript, Tailwind CSS v4,
PostgreSQL, Redis, dan Docker.
**Menjalankan secara lokal**

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && npm run build && php artisan serve
```

**Deployment dengan Docker**

```bash
cp .env.docker.example .env   # isi APP_KEY dan DB_PASSWORD
docker compose build && docker compose up -d
docker compose exec app php artisan migrate --force
```

Dokumentasi dalam repo ini menggunakan bahasa Inggris sebagai bahasa utama. Terjemahan ke
Bahasa Indonesia disediakan sebagai bahasa kedua dan direncanakan untuk tahap localization.

---

<div align="center">

**Made for the people who teach.** — LightLMS contributors

</div>
