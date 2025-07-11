<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\TraineeEnrollmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CashierController;
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
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {        
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');
        
            // Program Management Routes
    Route::get('/programs', [ProgramController::class, 'adminIndex'])->name('programs');
    Route::post('/programs', [ProgramController::class, 'adminStore'])->name('programs.store');
    Route::get('/programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/programs/{program}', [ProgramController::class, 'adminUpdate'])->name('programs.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'adminDestroy'])->name('programs.destroy');
        
        Route::get('/trainees', [TraineeController::class, 'adminIndex'])->name('trainees');
        Route::post('/trainees', [TraineeController::class, 'adminStore'])->name('trainees.store');
        Route::put('/trainees/{trainee}', [TraineeController::class, 'adminUpdate'])->name('trainees.update');
        Route::delete('/trainees/{trainee}', [TraineeController::class, 'adminDestroy'])->name('trainees.destroy');
        Route::patch('/trainees/{trainee}/status', [TraineeController::class, 'updateStatus'])->name('trainees.status');
        
        Route::get('/trainers', [TrainerController::class, 'adminIndex'])->name('trainers');
        Route::post('/trainers', [TrainerController::class, 'adminStore'])->name('trainers.store');
        Route::get('/trainers/{trainer}/edit', [TrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{trainer}', [TrainerController::class, 'adminUpdate'])->name('trainers.update');
        Route::delete('/trainers/{trainer}', [TrainerController::class, 'adminDestroy'])->name('trainers.destroy');
        
        Route::get('/payments', function () {
            return Inertia::render('Admin/Payments');
        })->name('payments');
        Route::post('/payments', function () {            
        })->name('payments.store');
        Route::put('/payments/{id}', function ($id) {            
        })->name('payments.update');
        Route::delete('/payments/{id}', function ($id) {            
        })->name('payments.destroy');
        Route::patch('/payments/{id}/status', function ($id) {            
        })->name('payments.status');
        
        Route::get('/trainings', function () {
            return Inertia::render('Admin/TrainingResults');
        })->name('trainings');
        Route::post('/trainings', function () {            
        })->name('trainings.store');
        Route::put('/trainings/{id}', function ($id) {            
        })->name('trainings.update');
        Route::delete('/trainings/{id}', function ($id) {            
        })->name('trainings.destroy');
        
        Route::get('/assessments', function () {
            return Inertia::render('Admin/AssestmentResults');
        })->name('assessments');
        Route::post('/assessments', function () {            
        })->name('assessments.store');
        Route::put('/assessments/{id}', function ($id) {            
        })->name('assessments.update');
        Route::delete('/assessments/{id}', function ($id) {            
        })->name('assessments.destroy');
        
        Route::get('/employments', function () {
            return Inertia::render('Admin/EmploymentsReferrals');
        })->name('employments');
        Route::post('/employments', function () {            
        })->name('employments.store');
        Route::put('/employments/{id}', function ($id) {            
        })->name('employments.update');
        Route::delete('/employments/{id}', function ($id) {            
        })->name('employments.destroy');
        
        Route::get('/reports', function () {
            return Inertia::render('Admin/Reports');
        })->name('reports');
        Route::post('/reports/generate', function () {            
        })->name('reports.generate');
        Route::post('/reports/export', function () {            
        })->name('reports.export');
        
        Route::get('/users', function () {
            return Inertia::render('Admin/Users');
        })->name('users');
        Route::post('/users', function () {            
        })->name('users.store');
        Route::put('/users/{id}', function ($id) {            
        })->name('users.update');
        Route::delete('/users/{id}', function ($id) {            
        })->name('users.destroy');
        Route::patch('/users/{id}/status', function ($id) {            
        })->name('users.status');
        
        Route::get('/analytics', function () {            
        })->name('analytics');
        Route::get('/settings', function () {            
        })->name('settings');
        Route::post('/backup', function () {            
        })->name('backup');
    });
    
    Route::prefix('officer')->name('officer.')->middleware('role:officer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'officer'])->name('dashboard');
        
            // Programs Management - Officers can create, update, delete programs
    Route::get('/programs', [ProgramController::class, 'index'])->name('programs');
    Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('/programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
        
        // Trainees Management - Officers can manage trainees
        Route::get('/trainees', [TraineeController::class, 'index'])->name('trainees');
        Route::post('/trainees', [TraineeController::class, 'store'])->name('trainees.store');
        Route::get('/trainees/{trainee}/edit', [TraineeController::class, 'edit'])->name('trainees.edit');
        Route::put('/trainees/{trainee}', [TraineeController::class, 'update'])->name('trainees.update');
        Route::delete('/trainees/{trainee}', [TraineeController::class, 'destroy'])->name('trainees.destroy');
        Route::patch('/trainees/{trainee}/status', [TraineeController::class, 'updateStatus'])->name('trainees.status');
        
        // Trainers Management - Officers can manage trainers
        Route::get('/trainers', [TrainerController::class, 'index'])->name('trainers');
        Route::post('/trainers', [TrainerController::class, 'store'])->name('trainers.store');
        Route::get('/trainers/{trainer}/edit', [TrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update');
        Route::delete('/trainers/{trainer}', [TrainerController::class, 'destroy'])->name('trainers.destroy');

        
        // Assessments Management - Officers can manage assessments
        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments');
        Route::post('/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
        Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit'])->name('assessments.edit');
        Route::put('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
        Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
        Route::post('/assessments/{assessment}/reassessment', [AssessmentController::class, 'reassessment'])->name('assessments.reassessment');

        // Trainee Enrollments - Officers can manage multiple program enrollments
        Route::get('/trainees/{trainee}/enroll', [TraineeEnrollmentController::class, 'create'])->name('trainees.enroll');
        Route::post('/trainees/{trainee}/enroll', [TraineeEnrollmentController::class, 'store'])->name('trainees.enroll.store');
        Route::get('/trainees/{trainee}/enrollment-history', [TraineeEnrollmentController::class, 'history'])->name('trainees.enrollment-history');
        Route::put('/enrollments/{enrollment}/status', [TraineeEnrollmentController::class, 'updateStatus'])->name('enrollments.update-status');
        Route::put('/enrollments/{enrollment}/payment', [TraineeEnrollmentController::class, 'updatePayment'])->name('enrollments.update-payment');
        Route::get('/trainees/{trainee}/available-programs', [TraineeEnrollmentController::class, 'getAvailablePrograms'])->name('trainees.available-programs');
        
        Route::get('/schedules', function () {
            return Inertia::render('Officer/Schedules');
        })->name('schedules');
        
        Route::get('/results', function () {
            return Inertia::render('Officer/Results');
        })->name('results');
    });
    
    Route::prefix('cashier')->name('cashier.')->middleware('role:cashier')->group(function () {
        Route::get('/dashboard', [CashierController::class, 'dashboard'])->name('dashboard');
        Route::get('/payments', [CashierController::class, 'payments'])->name('payments');
        Route::get('/receipts', [CashierController::class, 'receipts'])->name('receipts');
        Route::post('/payments/process', [CashierController::class, 'processPayment'])->name('payments.process');
        Route::post('/receipts/save', [CashierController::class, 'saveCustomReceipt'])->name('receipts.save');
        Route::put('/receipts/{customReceipt}', [CashierController::class, 'updateCustomReceipt'])->name('receipts.update');
        
        Route::get('/reports/financial', function () {            
        })->name('reports.financial');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
