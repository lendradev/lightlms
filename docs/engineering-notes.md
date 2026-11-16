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
