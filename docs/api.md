# REST API

LightLMS exposes a small HTTP API for integrations: a portal that shows a learner's
progress, a script that hands in assignments, a reporting job that reads grades.

The API lives under `/api/v1`. Everything is JSON in and JSON out.

---

## Getting a token

A token belongs to one person and acts with that person's permissions. Course-scoped
permissions apply exactly as they do on the web: a token for an instructor in one course
gives no access to another course they do not teach.

1. Sign in to LightLMS.
2. Open **API tokens** in the sidebar.
3. Create a token with a name that says what it is for.

The token is shown once, immediately after it is created. It cannot be recovered later,
only revoked. Store it where you store other credentials.

```
Authorization: Bearer <token>
```

Revoke a token from the same page when the integration stops. A revoked token stops
working on the next request.

---

## Rate limits

| Limit | Value |
|---|---|
| Requests per minute | 60 |
| Requests per day | 1000 |

The limit is counted per token, not per address, so one busy integration cannot lock out
other people behind the same network. Exceeding the limit answers with `429` and the
`Retry-After` header.

---

## Response shapes

A single record:

```json
{ "data": { "id": 1, "title": "Systems Thinking for Public Policy" } }
```

A list of records:

```json
{ "data": [ { "id": 1 } ], "links": { "first": null, "last": null }, "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 } }
```

Dates are ISO 8601 in UTC. Marks are numbers, never strings.

---

## Errors

Every failure uses the same body, so a client reads one field instead of parsing prose.

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": { "content": ["The content field is required."] }
  }
}
```

| `code` | Status | Meaning |
|---|---|---|
| `unauthenticated` | 401 | Missing, invalid or revoked token |
| `forbidden` | 403 | The token is valid but the person may not do this |
| `not_found` | 404 | No such record, or none the caller may see |
| `method_not_allowed` | 405 | Wrong HTTP method for this path |
| `validation_failed` | 422 | The body did not pass validation; `details` holds the field errors |
| `rate_limited` | 429 | Too many requests; see `Retry-After` |
| `server_error` | 500 | The server ran into a problem |

A `403` is a permission answer. A `404` is an existence answer. LightLMS answers `404`
where answering `403` would reveal that a record exists but belongs to somebody else.

---

## Endpoints

Base URL: `https://your-site.example/api/v1`

### `GET /me`

The person the token belongs to.

```json
{ "data": { "id": 12, "name": "Sam Student", "email": "sam@example.edu", "role": "student", "created_at": "2026-01-04T09:12:00+00:00" } }
```

### `GET /courses`

The published catalogue.

| Parameter | Type | Notes |
|---|---|---|
| `q` | string | Search in title, summary, code and instructor name |
| `level` | string | `beginner`, `intermediate` or `advanced` |
| `per_page` | integer | Default 15, maximum 100 |

### `GET /courses/{course}`

One course with its modules and activities. `{course}` is the slug.

### `GET /enrollments`

The courses the caller is enrolled in, with progress and status.

### `POST /enrollments`

Enrol the caller in a course.

```json
{ "course": "systems-thinking-for-public-policy" }
```

Answers `200` with the new enrolment. The course must be published and must allow
self-enrolment; otherwise the answer is `403`.

### `DELETE /enrollments/{course}`

Leave a course. Answers `204`.

### `GET /courses/{course}/participants`

The people enrolled in a course. Needs the `course.viewparticipants` capability, so a
learner gets `403`.

The email address of a participant is only included for the caller themselves and for
administrators.

### `GET /courses/{course}/progress/{user}`

The progress of one person in one course. A learner may read their own progress;
somebody who teaches the course may read anybody's.

### `GET /courses/{course}/grades`

The gradebook of a course.

- A learner sees only their own marks.
- Somebody who teaches the course sees every mark.

### `GET /assignments/{assignment}/submissions`

The attempts of the caller on one assignment.

### `POST /assignments/{assignment}/submissions`

Hand in an attempt.

```json
{ "content": "My mapping of the bus route system." }
```

Answers `201` with the new submission. The content needs at least 10 characters. The
attempt limit of the assignment applies, and a passed deadline without late submission
allowed answers `422`.

Hand-ins made through the API are audited like hand-ins made through the web.

### `GET /notifications`

The inbox of the caller, newest first. `limit` caps the number of items, default 50.

### `POST /notifications/{notification}/read`

Mark one notification as read.

---

## What the API deliberately does not expose

- Quiz answers, answer keys and marks before a submission.
- The contents of a course the caller is not part of.
- Passwords, tokens and other secrets of other people.
- Anything about a person that the caller cannot already see on the web.

---

## Example

```bash
TOKEN="1|your-token-here"

curl -s https://your-site.example/api/v1/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

```bash
curl -s https://your-site.example/api/v1/courses \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```
