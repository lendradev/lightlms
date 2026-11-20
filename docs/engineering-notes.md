# Engineering notes

Things that cost time, or nearly did. Each entry states the symptom, the cause, and the fix, so
the next person does not have to rediscover it.

## Laravel 11+ does not register `app/Exceptions/Handler.php`

**Symptom.** A custom error page never renders. A 404 shows Laravel's default error view.

**Cause.** Laravel 11 dropped the automatic registration of the exception handler class. A
handler living in `app/Exceptions/Handler.php` is dead code.

**Fix.** Register the render callback in `bootstrap/app.php`:

```php
$exceptions->render(function (\Throwable $e, Request $request) {
    // ...
});
```

Two traps come with it:

- The callback receives `($e, $request)`, not `($request, $e)`. The reversed order is silently
  ignored, so the handler never runs.
- The callback must return `null` for `ValidationException` and `AuthenticationException`.
  Returning a response there swallows the session errors and the login redirect, and the tests
  fail in a confusing way.

## `use Throwable;` in a file without a namespace prints a warning

**Symptom.** "Cannot modify header information" and a blank error page.

**Cause.** A `use` statement in a file with no namespace prints a warning to the output. The
warning is written before the headers, so the headers are already sent.

**Fix.** Use `\Throwable` inline in global files.

## Inertia page props do not accept a scalar for `has()`

**Symptom.** `TypeError: assertCount(): Argument #2 must be of type Countable|Traversable|array,
int given`.

**Cause.** `->has('key', 5)` is a count assertion, not an equality assertion.

**Fix.** Use `->where('key', 5)` for a scalar.

## A route model binding that cannot be resolved must fail closed

**Symptom.** A teacher could open the hand-ins of a course they do not own.

**Cause.** The instructor page resolved the course from the slug but read the assignment from a
different chain, so the ownership check passed on one object and the data came from another.

**Fix.** Resolve the course from the assignment inside the controller and abort when the chains
disagree:

```php
abort_unless($assignment->lesson?->module?->course_id === $course->id, 404);
```

The same check applies to a submission belonging to a different assignment, and to a question
belonging to a different quiz.

## Quiz answers must never reach the browser

**Symptom.** A learner could read the correct answer out of the page source.

**Cause.** The learner page and the grader page shared one payload builder.

**Fix.** `QuizService` has two methods: `questionsForLearner()` omits the answer key entirely,
`questionsForGrader()` includes it. A test asserts that the string `"answer"` does not appear in
the learner response, so a regression fails the build rather than shipping.

## Laravel Pint and the `\Throwable` rule fight each other

**Symptom.** `composer run lint` keeps rewriting `function (\Throwable $e, ...)` into
`function (Throwable $e, ...)`, which then breaks the global-file rule above.

**Cause.** Pint's `fully_qualified_strict_types` rule prefers an imported short name. The file
has no namespace, so the short name would be undefined.

**Fix.** Leave `bootstrap/app.php` as `\Throwable` and accept that Pint reports one style issue in
that file. The alternative, importing `Throwable`, brings back the warning-and-headers bug.

## Prettier runs inside the container, not on the host

**Symptom.** `pnpm run format:check` failed on files that had just been formatted.

**Cause.** Formatting ran in the Node container, which has its own filesystem. The host copy was
never updated.

**Fix.** Format in the container, then copy the files back:

```bash
for f in <files>; do docker exec sbx-lightlms-build sh -c "cat /work/app/$f" > "$f"; done
```

## Build assets do not travel between containers

**Symptom.** A new page 404s in the browser while the code is correct and the build succeeded.

**Cause.** The Node container and the PHP container have separate filesystems. The build lands in
the Node container only.

**Fix.** Stream the built assets over:

```bash
docker exec sbx-lightlms-node sh -c 'cd /work/app/public/build && tar -cf - .' \
  | docker exec -i sbx-lightlms-build sh -c \
      'rm -rf /work/deps/public-build && mkdir -p /work/deps/public-build && tar -C /work/deps/public-build -xf -'
```

## `pnpm` does not hoist peer dependencies

**Symptom.** The build fails with "failed to resolve import" for `react`.

**Cause.** `react` and `react-dom` were only peer dependencies of other packages.

**Fix.** Both are direct dependencies in `package.json`.

## The Inertia form type does not accept a loose record

**Symptom.** `tsc` rejected `useForm({ answers: {} as Record<string, unknown> })`.

**Cause.** `useForm` infers the value type from the initial object, and `unknown` is not
assignable to the inferred element type.

**Fix.** Give the form an explicit type parameter with a concrete value type:

```ts
type AnswerValue = string | string[] | boolean | null;

const form = useForm<{ answers: Record<string, AnswerValue> }>({ answers: {} });
```

## `router` has no `processing` flag

**Symptom.** `Property 'processing' does not exist on type 'Router'`.

**Cause.** `router` is the navigation helper, not the form helper. Only `useForm` exposes
`processing`.

**Fix.** Track the state locally with `useState`, and clear it in `onFinish`.

## An upload file name must never come from the client

**Symptom.** A crafted upload name such as `../../etc/passwd.pdf` decides where the file lands.

**Cause.** The original name was used to build the path on disk.

**Fix.** The path is generated on the server from a UUID, the original name is kept in the database
for display only, and the file never enters `public/`:

```php
$name = Str::uuid()->toString().'.'.$extension;
Storage::disk('local')->putFileAs(now()->format('Y/m'), $upload, $name);
```

The MIME type is checked against an allowlist, not a blocklist, and the size is capped. A test
uploads a name with `../` in it and asserts the stored path contains neither.

## The storage disk has a private root

`config/filesystems.php` points the `local` disk at `storage_path('app/private')`. The `public` disk
points at `storage_path('app/public')`. Uploaded files go to `local`, never to `public`, so they are
served through a controller that checks permission rather than by a direct URL.

## A file is served by a controller, not by a URL

**Symptom.** A student outside the course could read another student's attachment.

**Cause.** The file was written under the public disk.

**Fix.** `/files/{file}` is a controller route. It resolves the owning submission, then allows the
owner or anyone with `submission.viewall` in that course, and aborts otherwise. The response uses
the recorded original name, never the name on disk.

## A preference overrides the event default, per channel

**Symptom.** A person switched off email notifications but kept receiving in-app ones, or the
other way round.

**Cause.** The preference was read as a single boolean per event.

**Fix.** `notification_preferences` has a unique key on `(user_id, notification_event_id, channel)`.
A preference for `email` says nothing about `inapp`. When no row exists for a channel, the event
default decides. Both behaviours are covered by tests.

## The unread badge is shared through Inertia, not fetched per page

**Symptom.** The bell badge was stale until the page was reloaded.

**Cause.** The count was only computed on the notifications page.

**Fix.** `HandleInertiaRequests` shares `notifications.unread` with every page, so the header badge
is always right after any navigation.

## Screenshot file names collide across features

**Symptom.** Two screens both wanted `50-mobile-notifications.png`, and the catalogue pointed at
the wrong image.

**Cause.** The numbering scheme had `50-51` reserved for error states, and the mobile screens
started at `40`.

**Fix.** Check the existing numbers before adding a screenshot, and pick a free block. The
catalogue is the list of truth: `workspace/docs/screenshots.md`.

## A test that passes once is not a passing test

**Symptom.** The suite reported one failure on one run and none on the next.

**Cause.** A test depended on data left behind by another test, so the order of the run decided
the outcome.

**Fix.** Run the suite several times before believing it, and make every test build its own data.

## The auth manager keeps its guards between requests in one test
**Symptom.** A second API request in the same test was answered as the first caller: a student
read the participants of a course they do not teach.
**Cause.** The Sanctum guard is a `RequestGuard`, and the auth manager keeps the resolved user on
it. The first request in the test resolved the user, and the second request reused it.
**Fix.** Call `Auth::forgetGuards()` before a request that must be made as a different person.
The API test helper does it for every request.

## `syncItems` removes grade items that no longer match an activity

**Symptom.** A test that created a grade item by hand found no marks and no item.

**Cause.** `GradebookService::syncItems()` deletes every item whose source activity is gone, and
the API gradebook calls it first. A hand-made item with a made-up `source_id` did not survive.

**Fix.** In tests, build the item from a real activity of the course, so `syncItems()` keeps it.

## The certificate must be issued twice to be returned twice
**Symptom.** A test that called the issue method twice got a certificate the first time and
nothing the second time.
**Cause.** The method returned `null` when a certificate already existed, so the caller could
not tell "not eligible" from "already issued".
**Fix.** The method returns the existing certificate when there is one. A caller that needs to
know whether something was just issued checks the audit log, not the return value.

## The lock file was resolved on a newer PHP than the app supports
**Symptom.** `composer install` refused to install on PHP 8.3: the lock file wanted Symfony 8.1,
which requires PHP 8.4.

**Cause.** The lock file was generated on a PHP 8.5 machine. Composer resolves against the PHP it
runs on, so the lock file silently became a PHP 8.5 lock file.

**Fix.** `composer.json` pins the resolution target with `config.platform.php`, so the lock file is
resolved as if PHP 8.3 were the runtime while the app requirement stays `php: ^8.3`. Check
`composer.lock` for `platform-overrides` after every `composer update`.

## A Pages workflow with the wrong indentation never runs a single job
**Symptom.** The Pages run finished with `0 jobs` and conclusion `failure`, with no error message.

**Cause.** YAML indentation. `uses:` was indented one level deeper than the step it belongs to, so
the file parsed as a mapping inside a mapping and GitHub found no job to start.

**Fix.** Run `actionlint` over `.github/workflows/` before pushing. It catches the indentation, the
unknown inputs and the shell problems that a failed run only reports after the fact.

## `getPagesSite` returns 404 for every repository when the request is unauthenticated
**Symptom.** `GET /repos/{owner}/{repo}/pages` answered `404` even though the repository reports
`has_pages: true`.

**Cause.** The endpoint needs authentication. An anonymous request gets a 404 for every repository,
so the response says nothing about whether Pages is configured.

**Fix.** Do not read the API response as proof that Pages is off. Check the repository settings, or
call the endpoint with a token. The same applies to `actions/configure-pages`: with
`enablement: true` it needs a token that is not `GITHUB_TOKEN`.

## Git cannot store an empty directory
**Symptom.** The suite passed on the developer machine and failed on the runner with
`Test directory "tests/Unit" not found` and exit code 2, on every PHP version.

**Cause.** `tests/Unit` held no file. Git does not track directories, so the directory did not
exist in the clone, and PHPUnit treats a missing test directory as a fatal error, not as an empty
suite.

**Fix.** Put a file in the directory. A real unit test is better than a placeholder: it proves the
suite runs. The same trap applies to any directory listed in `phpunit.xml`.

## SQLite accepts a join to a table that has not been joined yet
**Symptom.** The suite passed locally and failed on the runner with
`missing FROM-clause entry for table "modules"` on PostgreSQL only.

**Cause.** The query joined `lessons` to `modules` before it joined `modules` to `courses`. SQLite
resolves a table that is not in the `FROM` list as a separate cross join and returns the rows
anyway. PostgreSQL refuses to build the query at all.

**Fix.** Join in the order the tables depend on each other: `courses` to `modules`, then `modules`
to `lessons`. Run the suite against the database the CI uses, not only against SQLite.

## PHPUnit reads the environment before Laravel does
**Symptom.** A test run reported 156 warnings instead of 156 passes, with
`file_get_contents(/work/.env)` as the reason.

**Cause.** `phpunit.xml` sets `DB_CONNECTION` and friends as `<env>` without `force="true"`, so
PHPUnit keeps an existing environment variable and ignores its own value. The `.env` file then
supplied the database connection, overriding the test configuration.

**Fix.** Put a `.env` in place before running the suite, or set the variables in the shell. The
run in CI copies `.env.example` to `.env` for the same reason.

## The PHP suite must not need a front-end build
**Symptom.** 50 of 170 tests failed with `Vite manifest not found at: public/build/manifest.json`,
on every PHP version, while the same suite passed on the developer machine.

**Cause.** The developer machine had a `public/build` directory left over from a local
`pnpm run build`. The CI runner had none. Both the root view and the error page call `@vite`, so
every request that rendered HTML failed, and the error page failed the same way, hiding the real
error behind a second `ViewException`.

**Fix.** `$this->withoutVite()` in `tests/TestCase.php`. The suite no longer reads the manifest,
so it runs on a clean checkout with no front-end build. The manifest is cached per path in a
static array, so a build that exists is still used.
## A static route segment swallows the ones after it
**Symptom.** `POST /teaching/{course}/gradebook/categories` returned 404 for a student, and the
category was never created for an instructor.
**Cause.** The route `POST /teaching/{course}/gradebook/{item}` was declared before the fixed
segments. Laravel matches routes in order, so `{item}` captured the literal string `categories`
and the request was routed to the grade-saving action with a non-numeric item.
**Fix.** Declare the fixed segments first and the parameterised one last. The order of the routes
in `routes/web.php` is part of the routing contract, not a matter of taste.
## `validated` only holds the keys the rules allowed
**Symptom.** Creating a manual grade item returned 500 with `Undefined array key "weight"`.
**Cause.** The controller read `$validated['weight']` directly, but a `nullable` rule on a field
the request never sent leaves that key out of the validated array entirely. Only the keys that
were actually present, or that have a default, are present in the result.
**Fix.** Read optional fields with `array_key_exists('weight', $validated)`, the same way the item
update action already did.
## A cookie that the framework encrypts cannot be read by the middleware that sets it
**Symptom.** The locale cookie was written by the application but the next request came back in
English, and the test suite failed with `DecryptException: The payload is invalid`.
**Cause.** Laravel encrypts every cookie by default. The locale middleware read the raw cookie
value, which was the encrypted blob, so the locale never matched and the middleware fell back to
the default. The test helper `withCookie` encrypts the value for the request, so the test read an
encrypted value too and got a second layer of encryption.
**Fix.** Exclude the cookie from encryption with `$middleware->encryptCookies(except: [...])`, and
use `withUnencryptedCookies()` in the tests. The value in the cookie is a language code, so there
is nothing to protect.
## A private method whose signature grew breaks every caller
**Symptom.** The gradebook of a course and the transcript of a learner both returned 500 with
`Too few arguments to function GradebookService::aggregateMax(), 1 passed in ... and exactly 2
expected`. The course total still worked, so the bug was easy to miss.
**Cause.** `aggregateMax()` was changed from one argument to two when the second aggregation layer
was added, and one of the three call sites was left calling it with the old signature. The
failure only shows up on the pages that render a category subtotal, and only once a course has
marks in a category.
**Fix.** Pass the aggregation of the category at every call site. A private method with a changed
signature has no compiler to catch the callers, so the page that uses it needs a test.
## A seeder that only appends leaves the demo data inconsistent
**Symptom.** The gradebook showed six categories instead of three: Exams, Homework and Project,
each twice. Every category card showed a dash for every learner, and the transcript listed Exams
twice.
**Cause.** The screenshot seeder created the three categories with `saveCategory()` on every run.
The seeder ran a second time against a database that already had the categories, so the
categories were duplicated. The items pointed at the second copy, while the page listed the first
copy, so the lookup by category id found nothing.
**Fix.** Look the category up by name before creating it, and delete any duplicate that is already
there. A seeder that is run again against an existing database has to converge on the same state,
not add to it.
## `UploadedFile::moveTo()` does not exist
**Symptom.** Restoring a backup in the admin area returned 500 with
`BadMethodCallException: Method Illuminate\Http\UploadedFile::moveTo does not exist`.
**Cause.** `moveTo()` was never part of Symfony's `UploadedFile`. The methods are `move()` and
`store()`. The code had been written from memory, and no test reached the controller, so the
method was never called in a test run.
**Fix.** Use `move($directory, $name)`, which takes the target folder and the target file name.
`move()` also refuses to overwrite an existing file, so the name still has to be generated on
the server.

## A test that posts a path where a file is expected proves nothing
**Symptom.** The backup round-trip test passed while the restore action was broken.
**Cause.** The test posted `['archive' => '/path/to/backup.tar']`, a string, where the controller
expected an uploaded file. The `file` validation rule rejected it and the request returned a
redirect, which is exactly what `assertRedirect()` accepts. The follow-up `assertDatabaseHas()`
passed as well, because the course had been soft deleted rather than removed.
**Fix.** Post a real `UploadedFile`, assert `assertSessionHasNoErrors()`, and assert on the state
that the action is supposed to change. A redirect is the answer to a validation failure, not to a
successful action.

## A tar member is not addressed by its basename
**Symptom.** Restoring a backup brought the database back but left every uploaded file missing.
**Cause.** `PharData` members were read with `getFilename()`, which is the basename alone. The
filter that only accepts members under `storage/` therefore rejected every member, because the
basename never carries that prefix. The same loop also lost the folder structure of the storage
tree, so a file restored from `storage/2026/01/note.pdf` would have landed in the root.
**Fix.** Take the path from the phar URI of the member and strip the archive prefix. Read the
dump with `getContent()` as well: a `PharFile` cast to a string gives its path, not its contents.

## Reserved words break a hand written dump
**Symptom.** Restoring a backup failed with `SQLSTATE[HY000]: General error: 1 near "group":
syntax error`.
**Cause.** The dump wrote column names as they came from the row, without quoting. The settings
table has columns called `key` and `group`, which are reserved words in SQLite, so the `INSERT`
could not be parsed.
**Fix.** Quote every identifier for the driver in use: double quotes on SQLite and PostgreSQL,
backticks on MySQL. Quote string literals for the driver as well, because MySQL treats a
backslash as an escape character.

## A rate limit belongs on the way in
**Symptom.** Nothing stopped a script from trying a password thousands of times a minute.
**Cause.** Only the API had a rate limit. The web sign-in, the registration form and the setup
wizard were open, because a limiter that is not registered in `AppServiceProvider` does nothing.
**Fix.** Register a limiter per form and attach it to the route. Count sign-in attempts per
address and per email address, so one attacker cannot lock a whole office out and one account
cannot be guessed from many addresses.

## A session cookie does not make a second browser in a test

**Symptom.** A test that wanted a second browser to reset a password while the first browser
stayed signed in failed with "the expected notification was not sent": the request for the reset
link came back as a redirect to the dashboard.

**Cause.** The test client keeps one session, and `withCookie()` only changes the cookie that is
sent. The session store itself was the same one, so the second request was still the signed-in
person, and the `guest` middleware turned it away.

**Fix.** Put the session aside instead: keep the attributes of the signed-in session, empty the
session, make the request as a guest, and put the saved attributes back afterwards. That is what
the test for a password reset does now.

## A password change ends open sessions, and the test suite noticed

**Symptom.** Four unrelated tests failed with a redirect to the login page after the session
middleware that ends a stale session was turned on.

**Cause.** The middleware compares the password hash the session was opened with against the
hash the account has now. `actingAs()` swaps the user on the guard but leaves that hash alone, so
the middleware read a hash of the previous user, decided the session was stale and logged it
out.

**Fix.** `tests/TestCase.php` drops the stored hash when the acting user changes, so the
middleware stores the hash of the new user. The real browser does the same, because the session
is opened by the sign-in that follows.

## The vendor directory cannot be a symlink out of the source tree

**Symptom.** `Class "App\Support\Locale" not found` after the dependency tree was moved to a
shared volume and linked into the source tree.

**Cause.** Composer derives the base directory of its class map from the parent of the vendor
directory. A vendor directory that lives elsewhere makes the class map point at the wrong place.

**Fix.** Keep the vendor directory inside the application tree. `docker/sync-container.sh` parks
it in a directory outside the tree while the source is replaced and moves it back afterwards, so
a sync never wipes an install.
