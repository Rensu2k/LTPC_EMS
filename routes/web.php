<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\AssessmentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::user()) {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('officer')) {
            return redirect()->route('officer.dashboard');
        } elseif ($user->hasRole('cashier')) {
            return redirect()->route('cashier.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Admin routes
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');
    
    Route::get('/admin/users', function () {
        return Inertia::render('Admin/Users');
    })->name('admin.users');
    
    Route::get('/admin/reports', function () {
        return Inertia::render('Admin/Reports');
    })->name('admin.reports');

    // Officer routes
    Route::get('/officer/dashboard', [App\Http\Controllers\DashboardController::class, 'officer'])->name('officer.dashboard');
    
    Route::get('/officer/courses', [CourseController::class, 'index'])->name('officer.courses');
    Route::post('/officer/courses', [CourseController::class, 'store'])->name('officer.courses.store');
    Route::get('/officer/courses/{course}/edit', [CourseController::class, 'edit'])->name('officer.courses.edit');
    Route::get('/officer/courses/{course}', [CourseController::class, 'show'])->name('officer.courses.show');
    Route::put('/officer/courses/{course}', [CourseController::class, 'update'])->name('officer.courses.update');
    Route::delete('/officer/courses/{course}', [CourseController::class, 'destroy'])->name('officer.courses.destroy');
    
    Route::get('/officer/trainees', [TraineeController::class, 'index'])->name('officer.trainees');
    Route::post('/officer/trainees', [TraineeController::class, 'store'])->name('officer.trainees.store');
    Route::get('/officer/trainees/{trainee}/edit', [TraineeController::class, 'edit'])->name('officer.trainees.edit');
    Route::get('/officer/trainees/{trainee}', [TraineeController::class, 'show'])->name('officer.trainees.show');
    Route::put('/officer/trainees/{trainee}', [TraineeController::class, 'update'])->name('officer.trainees.update');
    Route::delete('/officer/trainees/{trainee}', [TraineeController::class, 'destroy'])->name('officer.trainees.destroy');
    
    Route::get('/officer/trainers', [TrainerController::class, 'index'])->name('officer.trainers');
    Route::post('/officer/trainers', [TrainerController::class, 'store'])->name('officer.trainers.store');
    Route::get('/officer/trainers/{trainer}/edit', [TrainerController::class, 'edit'])->name('officer.trainers.edit');
    Route::get('/officer/trainers/{trainer}', [TrainerController::class, 'show'])->name('officer.trainers.show');
    Route::put('/officer/trainers/{trainer}', [TrainerController::class, 'update'])->name('officer.trainers.update');
    Route::delete('/officer/trainers/{trainer}', [TrainerController::class, 'destroy'])->name('officer.trainers.destroy');
    
    Route::get('/officer/assessments', [AssessmentController::class, 'index'])->name('officer.assessments');
    Route::post('/officer/assessments', [AssessmentController::class, 'store'])->name('officer.assessments.store');
    Route::get('/officer/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('officer.assessments.edit');
    Route::get('/officer/assessments/{assessment}', [AssessmentController::class, 'show'])->name('officer.assessments.show');
    Route::put('/officer/assessments/{assessment}', [AssessmentController::class, 'update'])->name('officer.assessments.update');
    Route::delete('/officer/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('officer.assessments.destroy');

    // Cashier routes
    Route::get('/cashier/dashboard', function () {
        return Inertia::render('Cashier/Dashboard');
    })->name('cashier.dashboard');
    
    Route::get('/cashier/payments', function () {
        return Inertia::render('Cashier/Payments');
    })->name('cashier.payments');
    
    Route::get('/cashier/receipts', function () {
        return Inertia::render('Cashier/Receipts');
    })->name('cashier.receipts');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
