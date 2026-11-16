<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Learning\AssignmentController as LearningAssignmentController;
use App\Http\Controllers\Learning\QuizController as LearningQuizController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Teaching\AssignmentController as TeachingAssignmentController;
use App\Http\Controllers\Teaching\CertificateController as TeachingCertificateController;
use App\Http\Controllers\Teaching\GradebookController;
use App\Http\Controllers\Teaching\QuizController as TeachingQuizController;
use App\Http\Controllers\Teaching\RosterController;
use App\Http\Controllers\TeachingController;
use Illuminate\Support\Facades\Route;

Route::middleware('installed')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/certificates/verify/{code}', [CertificateController::class, 'verify'])
        ->name('certificates.verify');
});

Route::prefix('setup')->name('setup.')->group(function (): void {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::post('/', [SetupController::class, 'install'])->name('install');
});

Route::middleware('installed')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('guest');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::middleware('auth')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/learning', [LearningController::class, 'index'])->name('learning.index');
        Route::get('/learning/{course}', [LearningController::class, 'show'])->name('learning.course');
        Route::get('/learning/{course}/grades', [LearningController::class, 'grades'])->name('learning.grades');
        Route::post('/learning/lessons/{lesson}/complete', [LearningController::class, 'complete'])
            ->name('learning.lesson.complete');

        Route::get('/assignments/{assignment}', [LearningAssignmentController::class, 'show'])
            ->name('learning.assignments.show');
        Route::post('/assignments/{assignment}', [LearningAssignmentController::class, 'store'])
            ->name('learning.assignments.store');

        Route::get('/quizzes/{quiz}', [LearningQuizController::class, 'show'])
            ->name('learning.quizzes.show');
        Route::post('/quizzes/{quiz}', [LearningQuizController::class, 'store'])
            ->name('learning.quizzes.store');

        Route::get('/files/{file}', [FileController::class, 'show'])
            ->name('files.show');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])
            ->name('notifications.preferences');
        Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences'])
            ->name('notifications.preferences.update');
        Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
            ->name('notifications.read-all');

        Route::get('/settings/api-tokens', [ApiTokenController::class, 'index'])
            ->name('settings.api-tokens.index');
        Route::post('/settings/api-tokens', [ApiTokenController::class, 'store'])
            ->name('settings.api-tokens.store');
        Route::delete('/settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
            ->name('settings.api-tokens.destroy');

        Route::get('/certificates', [CertificateController::class, 'index'])
            ->name('certificates.index');
        Route::get('/certificates/{code}', [CertificateController::class, 'show'])
            ->name('certificates.show');

        Route::middleware('role:instructor,admin')->group(function (): void {
            Route::get('/teaching', [TeachingController::class, 'index'])->name('teaching.index');
            Route::get('/teaching/{course}', [TeachingController::class, 'show'])->name('teaching.course');
            Route::post('/teaching/courses', [TeachingController::class, 'store'])->name('teaching.courses.store');
            Route::put('/teaching/courses/{course}', [TeachingController::class, 'update'])->name('teaching.courses.update');
            Route::post('/teaching/courses/{course}/publish', [TeachingController::class, 'publish'])->name('teaching.courses.publish');
            Route::post('/teaching/courses/{course}/modules', [TeachingController::class, 'storeModule'])->name('teaching.modules.store');
            Route::post('/teaching/courses/{course}/lessons', [TeachingController::class, 'storeLesson'])->name('teaching.lessons.store');

            Route::get('/teaching/{course}/gradebook', [GradebookController::class, 'show'])->name('teaching.gradebook');
            Route::get('/teaching/{course}/gradebook/settings', [GradebookController::class, 'settings'])->name('teaching.gradebook.settings');
            Route::get('/teaching/{course}/gradebook/export', [GradebookController::class, 'export'])->name('teaching.gradebook.export');
            Route::post('/teaching/{course}/gradebook/categories', [GradebookController::class, 'storeCategory'])->name('teaching.gradebook.categories.store');
            Route::put('/teaching/{course}/gradebook/categories/{category}', [GradebookController::class, 'updateCategory'])->name('teaching.gradebook.categories.update');
            Route::delete('/teaching/{course}/gradebook/categories/{category}', [GradebookController::class, 'destroyCategory'])->name('teaching.gradebook.categories.destroy');
            Route::post('/teaching/{course}/gradebook/items', [GradebookController::class, 'storeManualItem'])->name('teaching.gradebook.items.store');
            Route::put('/teaching/{course}/gradebook/items/{item}', [GradebookController::class, 'updateItem'])->name('teaching.gradebook.items.update');
            Route::put('/teaching/{course}/gradebook/settings', [GradebookController::class, 'updateSettings'])->name('teaching.gradebook.settings.update');
            Route::post('/teaching/{course}/gradebook/{item}', [GradebookController::class, 'store'])->name('teaching.gradebook.store');

            Route::get('/teaching/{course}/roster', [RosterController::class, 'index'])->name('teaching.roster');
            Route::post('/teaching/{course}/roster', [RosterController::class, 'store'])->name('teaching.roster.store');
            Route::put('/teaching/{course}/roster/{user}', [RosterController::class, 'update'])->name('teaching.roster.update');
            Route::delete('/teaching/{course}/roster/{user}', [RosterController::class, 'destroy'])->name('teaching.roster.destroy');

            Route::get('/teaching/{course}/assignments/{assignment}', [TeachingAssignmentController::class, 'show'])
                ->name('teaching.assignments.show');
            Route::post('/teaching/{course}/assignments/{assignment}/{submission}/grade', [TeachingAssignmentController::class, 'grade'])
                ->name('teaching.assignments.grade');

            Route::get('/teaching/{course}/quizzes/{quiz}', [TeachingQuizController::class, 'show'])
                ->name('teaching.quizzes.show');
            Route::post('/teaching/{course}/quizzes/{quiz}/questions', [TeachingQuizController::class, 'storeQuestion'])
                ->name('teaching.quizzes.questions.store');
            Route::delete('/teaching/{course}/quizzes/{quiz}/questions/{question}', [TeachingQuizController::class, 'destroyQuestion'])
                ->name('teaching.quizzes.questions.destroy');
            Route::post('/teaching/{course}/quizzes/{quiz}/attempts/{attempt}/mark', [TeachingQuizController::class, 'mark'])
                ->name('teaching.quizzes.attempts.mark');

            Route::post('/teaching/{course}/lessons/{lesson}/files', [TeachingController::class, 'storeFile'])
                ->name('teaching.lessons.files.store');
            Route::delete('/teaching/{course}/files/{file}', [TeachingController::class, 'destroyFile'])
                ->name('teaching.files.destroy');

            Route::get('/teaching/{course}/certificates', [TeachingCertificateController::class, 'index'])
                ->name('teaching.certificates.index');
            Route::put('/teaching/{course}/certificates/rule', [TeachingCertificateController::class, 'updateRule'])
                ->name('teaching.certificates.rule.update');
            Route::post('/teaching/{course}/certificates', [TeachingCertificateController::class, 'store'])
                ->name('teaching.certificates.store');
            Route::delete('/teaching/{course}/certificates/{certificate}', [TeachingCertificateController::class, 'destroy'])
                ->name('teaching.certificates.destroy');
        });
    });
});
