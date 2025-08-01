<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentResultsController;
use App\Http\Controllers\EmploymentController;
use App\Http\Controllers\TraineeEnrollmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        
            // Program Management Routes - READ ONLY for Admin
        Route::get('/programs', [ProgramController::class, 'adminIndex'])->name('programs');
        Route::get('/programs/{program}/enrollments', [ProgramController::class, 'adminEnrollments'])->name('programs.enrollments');
        // NOTE: CRUD operations removed - only Officers can create/edit/delete programs
        
        // Trainee Management Routes - READ ONLY for Admin  
        Route::get('/trainees', [TraineeController::class, 'adminIndex'])->name('trainees');
        // NOTE: CRUD operations removed - only Officers can create/edit/delete trainees
        
        // Trainer Management Routes - READ ONLY for Admin
        Route::get('/trainers', [TrainerController::class, 'adminIndex'])->name('trainers');
        // NOTE: CRUD operations removed - only Officers can create/edit/delete trainers
        
        Route::get('/payments', [PaymentController::class, 'adminIndex'])->name('payments');
        // CRUD routes removed - admin has read-only access to payments
        
        Route::get('/trainings', function () {
            return Inertia::render('Admin/TrainingResults');
        })->name('trainings');
        Route::post('/trainings', function () {            
        })->name('trainings.store');
        Route::put('/trainings/{id}', function ($id) {            
        })->name('trainings.update');
        Route::delete('/trainings/{id}', function ($id) {            
        })->name('trainings.destroy');
        
        Route::get('/assessments', [AssessmentResultsController::class, 'index'])->name('assessments');
        Route::post('/assessments', [AssessmentResultsController::class, 'store'])->name('assessments.store');
        Route::put('/assessments/{assessment}', [AssessmentResultsController::class, 'update'])->name('assessments.update');
        Route::delete('/assessments/{assessment}', [AssessmentResultsController::class, 'destroy'])->name('assessments.destroy');
        
        Route::get('/employments', [EmploymentController::class, 'index'])->name('employments');
        Route::post('/employments', [EmploymentController::class, 'store'])->name('employments.store');
        Route::put('/employments/{id}', [EmploymentController::class, 'update'])->name('employments.update');
        Route::delete('/employments/{id}', [EmploymentController::class, 'destroy'])->name('employments.destroy');
        

        
        // Trainee Enrollment History - Admins can view enrollment history (read-only)
        Route::get('/trainees/{trainee}/enrollment-history', [TraineeEnrollmentController::class, 'history'])->name('trainees.enrollment-history');
        
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.status');
        
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
