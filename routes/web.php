<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Learning\AssignmentController as LearningAssignmentController;
use App\Http\Controllers\Learning\QuizController as LearningQuizController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Teaching\AssignmentController as TeachingAssignmentController;
use App\Http\Controllers\Teaching\GradebookController;
use App\Http\Controllers\Teaching\QuizController as TeachingQuizController;
use App\Http\Controllers\Teaching\RosterController;
use App\Http\Controllers\TeachingController;
use Illuminate\Support\Facades\Route;

Route::middleware('installed')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
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

    Route::middleware('auth')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/learning', [LearningController::class, 'index'])->name('learning.index');
        Route::get('/learning/{course}', [LearningController::class, 'show'])->name('learning.course');
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

        Route::middleware('role:instructor,admin')->group(function (): void {
            Route::get('/teaching', [TeachingController::class, 'index'])->name('teaching.index');
            Route::get('/teaching/{course}', [TeachingController::class, 'show'])->name('teaching.course');
            Route::post('/teaching/courses', [TeachingController::class, 'store'])->name('teaching.courses.store');
            Route::put('/teaching/courses/{course}', [TeachingController::class, 'update'])->name('teaching.courses.update');
            Route::post('/teaching/courses/{course}/publish', [TeachingController::class, 'publish'])->name('teaching.courses.publish');
            Route::post('/teaching/courses/{course}/modules', [TeachingController::class, 'storeModule'])->name('teaching.modules.store');
            Route::post('/teaching/courses/{course}/lessons', [TeachingController::class, 'storeLesson'])->name('teaching.lessons.store');

            Route::get('/teaching/{course}/gradebook', [GradebookController::class, 'show'])->name('teaching.gradebook');
            Route::post('/teaching/{course}/gradebook/{item}', [GradebookController::class, 'store'])->name('teaching.gradebook.store');
            Route::get('/teaching/{course}/gradebook/export', [GradebookController::class, 'export'])->name('teaching.gradebook.export');

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
        });
    });
});
