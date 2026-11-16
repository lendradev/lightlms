# AGENTS.md — Guidelines for AI agents contributing to LightLMS

This file is for AI coding agents (Claude Code, Codex, Cursor, Kilo, Aider, Copilot, or any
other tool) working on this repository. It is also useful to humans who want a compact map of
the codebase before making a change.

Read this file once at the start of a session, then follow it. Instructions from the human
maintainer in the chat always win over this file; if they conflict, say so in one line and
proceed with the human's version.

## 1. What this project is

LightLMS is a self-hosted, open-source Learning Management System built on Laravel 13, Inertia.js
v3, React 19 and Tailwind CSS v4. It is a conventional PHP application with a React front end:
no custom framework, no bespoke build pipeline, no server-rendered Blade views except the
Inertia root template.

The repository is a single PHP application. There is no separate front-end package, no
monorepo, and no separate API service.

## 2. Repository layout

```
app/                PHP application code (MVC + services)
  Enums/            Backed enums for domain values (CourseRole, ActivityType, QuestionType, ...)
  Http/Controllers/ HTTP layer. Learning/ holds learner-facing pages, Teaching/ holds instructor pages
  Models/           Eloquent models
  Policies/         Authorisation checks
  Services/         Domain logic that is not tied to HTTP (GradebookService, QuizService, ...)
  Support/          Small shared helpers
bootstrap/app.php   Laravel 11+ application bootstrap. This is where the custom error page is registered
config/             Configuration
database/
  factories/        Model factories used by tests
  migrations/       Schema. One migration per domain concern
  seeders/          DemoSeeder builds a complete demo environment
resources/
  css/app.css       Design tokens. The single source of truth for colour, spacing and radius
  js/components/    shadcn/ui components (ui/ folder) and shared components (AppShell, ThemeToggle)
  js/lib/           Front-end helpers
  js/pages/         Inertia page components, one per route
  views/            Only the Inertia root template and the themed error page
routes/web.php      All web routes
tests/Feature/      HTTP-level tests that exercise real routes and permissions
tests/Unit/         Pure unit tests
docs/screenshots/   Screenshot catalogue, light and dark
```

## 3. Setup

```bash
corepack enable pnpm     # pnpm 12.21.0 is pinned in package.json
composer install
pnpm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
pnpm run build
```

For a working demo environment in a container, see `docker/demo.sh`. For the full local
toolchain in containers, see `docker/dev.sh`.

## 4. Commands

Run these before proposing a change. A change that breaks lint, type-check or tests is not done.

| Command | What it does |
|---|---|
| `php artisan test` | Full PHPUnit suite (HTTP-level feature tests plus unit tests) |
| `php artisan test --filter AssignmentTest` | One test file |
| `composer run lint` | Laravel Pint, formats PHP |
| `pnpm run typecheck` | `tsc --noEmit` |
| `pnpm run lint` | ESLint |
| `pnpm run format:check` | Prettier, read-only check |
| `pnpm run build` | Vite production build into `public/build/` |
| `pnpm run dev` | Vite dev server with hot reload |
| `php artisan serve` | PHP dev server |

`composer run setup` does install, key generation, migrate and build in one go.

## 5. Architecture rules

### Server-side authorisation, always

Every endpoint checks permissions on the server. The UI hiding a button is a convenience, never
the control. Course-scoped permission checks are fail-closed: when the course cannot be resolved
from the route model binding, the request is aborted rather than allowed.

- `PermissionService` resolves what a user may do inside one course, based on `course_user.role`.
- `users.role` is the site-wide role (`student`, `instructor`, `admin`). `course_user.role` is the
  role inside one course (`student`, `assistant`, `instructor`). They are different things.
- Inertia page components in `resources/js/pages/Learning/` belong to learners, and those in
  `resources/js/pages/Teaching/` belong to instructors. Keep the split.

### Keep answers on the server

Quiz answers and grading logic live in `QuizService`. The learner-facing page never receives the
correct answer, the answer key, or the mark before they submit. If a change would send answer
data to the browser, it is wrong.

### Domain logic belongs in services

Anything that is not "read a request, return a response" belongs in `app/Services`. Controllers
should stay thin: validate, resolve the course, check permission, call the service, return an
Inertia response or a redirect.

### Audit what matters

State-changing actions that affect other people are recorded through `AuditService::record()`:
submission creation, grading, quiz attempts, roster changes, manual marks. The audit row keeps
the actor, the course, the before and after values, the IP address and the user agent.

### Schema changes

One migration per domain concern, named `YYYY_MM_DD_HHMMSS_verb_description.php`. Add the
migration, the model, and the factory if the model needs one. Existing tables are not rewritten
in place unless there is a reason to.

## 6. Front-end rules

### Design tokens are the only source of colour

Never write a raw hex, `rgb()` or `oklch()` value in a component. Use the semantic tokens from
`resources/css/app.css`: `bg-background`, `text-foreground`, `text-muted-foreground`, `bg-primary`,
`bg-secondary`, `border-border`, `text-destructive`, `bg-muted`, `bg-accent`.

That file holds the light theme in `:root` and the dark theme in `.dark`. A component that hard-codes
a colour breaks in one of the two themes.

### Light and dark mode are both mandatory

Every new screen must look correct in both themes. Run the app, toggle the theme, and check.
Dark mode is not a filter applied on top of the light design; it has its own token values.

### Components

Use the existing shadcn/ui components in `resources/js/components/ui/`. Do not add a new one for
something that already exists. Check the folder before writing a new component, and add one only
when the need is real.

Common ones already in use: `Button`, `Card`, `Badge`, `Input`, `Textarea`, `Label`, `Table`,
`Select`, `Checkbox`, `RadioGroup`, `Progress`, `Alert`, `Dialog`.

### Pages

- One Inertia page component per route, named after the route.
- Page components receive data through props; they do not fetch on their own.
- Use `useForm` for form state and `router` for actions that are not a form.
- Type the props. The project runs `tsc --noEmit` in strict mode.

### Accessibility

Every screen needs keyboard operability, visible focus, AA contrast for text, and a sensible
label for every input. Use real `<label htmlFor>` elements, not placeholder text as a label.

## 7. Testing rules

- Every behavioural change comes with a test.
- Feature tests live in `tests/Feature/` and exercise real routes with real authentication, so
  they cover routing, permission checks and controller behaviour together.
- Use factories. Do not build model instances field by field in a test when a factory exists.
- Assert on both the happy path and the refusal path. A permission check that is never tested
  is a permission check that does not exist.
- Do not weaken an assertion to make a test pass. Fix the code.

## 8. Security rules

- Never commit secrets, API keys, tokens or `.env`. `.env.example` is the template.
- Validate every input at the boundary. Use the validation rules of the framework, not manual
  checks scattered through the controller.
- Use parameterised queries. Never build SQL by concatenating user input.
- Escape output. React escapes by default; do not reach for `dangerouslySetInnerHTML`.
- Check permissions server-side for every state-changing endpoint.
- File uploads, when implemented, must validate MIME type and size, generate the stored file name
  on the server, and store outside `public/`.

## 9. Dependencies

- The package manager is **pnpm**, pinned to 12.21.0 in `package.json`. Do not use npm or Yarn.
- Every dependency version is pinned exactly. No `^`, `~`, `latest` or `*`.
- Before adding a dependency, check whether the standard library or an existing dependency already
  covers the need. A dependency is permanent attack surface.
- Never run `pnpm add` or `composer require` inside a container that has the host filesystem
  mounted without checking the sandbox rules the maintainer follows.

## 10. Commit conventions

Use Conventional Commits, in English, with the AI attribution trailer:

```
feat(grading): mark quiz essays by hand

Short answers and essays cannot be scored automatically, so the grader
gets a panel to give each one a mark and a note.

Prompted-By: Lendra
Generated-By: Lendra AI Agents
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`,
`revert`. Scope the commit to the component that changed.

## 11. Documentation

- `README.md` is the public entry point. Keep it in English.
- `docs/screenshots.md` is the screenshot catalogue. Every screen in both themes is listed there.
- `CONTRIBUTING.md` covers the human contribution workflow.
- When you add a screen, add its screenshot to the catalogue in both themes.

## 12. Things to avoid

- Do not add TanStack Query, TanStack Router or TanStack Table. Inertia handles routing, data
  loading, forms and validation.
- Do not hard-code colours, spacing or radius values in components.
- Do not add a second front-end framework, a second UI kit, or a state manager.
- Do not create a new abstraction for something that exists once. Ten clear lines beat a fifty
  line helper class.
- Do not reformat code you did not change.
- Do not commit build output, `.env`, or editor directories.

## 13. Definition of done

A change is done when:

1. `php artisan test` passes.
2. `composer run lint`, `pnpm run typecheck`, `pnpm run lint` and `pnpm run build` pass.
3. New behaviour is covered by a test, including the permission refusal path.
4. New screens work in light and dark mode, are keyboard operable, and have empty, loading and
   error states.
5. No secrets, no personal data, and no unexplained generated files are in the diff.
6. The screenshot catalogue is updated when a screen is added or changed.
