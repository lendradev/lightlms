<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| The public API of LightLMS. Every route here needs a bearer token; the
| token belongs to one person and carries that person's permissions, so the
| course-scoped checks stay the same as on the web.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/me', [ApiController::class, 'me'])->name('api.v1.me');

    Route::get('/courses', [ApiController::class, 'courses'])->name('api.v1.courses.index');
    Route::get('/courses/{course}', [ApiController::class, 'course'])->name('api.v1.courses.show');
    Route::get('/courses/{course}/participants', [ApiController::class, 'participants'])
        ->name('api.v1.courses.participants');
    Route::get('/courses/{course}/progress/{user}', [ApiController::class, 'progress'])
        ->name('api.v1.courses.progress');

    Route::get('/enrollments', [ApiController::class, 'enrollments'])->name('api.v1.enrollments.index');
    Route::post('/enrollments', [ApiController::class, 'enroll'])->name('api.v1.enrollments.store');
    Route::delete('/enrollments/{course}', [ApiController::class, 'unenroll'])->name('api.v1.enrollments.destroy');

    Route::get('/courses/{course}/grades', [GradeController::class, 'index'])->name('api.v1.courses.grades');

    Route::get('/assignments/{assignment}/submissions', [SubmissionController::class, 'index'])
        ->name('api.v1.assignments.submissions.index');
    Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])
        ->name('api.v1.assignments.submissions.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('api.v1.notifications.read');
});
