<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\SetupController;
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

        Route::middleware('role:instructor,admin')->group(function (): void {
            Route::get('/teaching', [TeachingController::class, 'index'])->name('teaching.index');
            Route::get('/teaching/{course}', [TeachingController::class, 'show'])->name('teaching.course');
            Route::post('/teaching/courses', [TeachingController::class, 'store'])->name('teaching.courses.store');
            Route::put('/teaching/courses/{course}', [TeachingController::class, 'update'])->name('teaching.courses.update');
            Route::post('/teaching/courses/{course}/publish', [TeachingController::class, 'publish'])->name('teaching.courses.publish');
            Route::post('/teaching/courses/{course}/modules', [TeachingController::class, 'storeModule'])->name('teaching.modules.store');
            Route::post('/teaching/courses/{course}/lessons', [TeachingController::class, 'storeLesson'])->name('teaching.lessons.store');
        });
    });
});
