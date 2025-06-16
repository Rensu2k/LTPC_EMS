<?php

use App\Http\Controllers\ProfileController;
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
    // Admin routes with role-based access control
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        // Programs Management
        Route::get('/programs', function () {
            return Inertia::render('Admin/Program');
        })->name('programs');
        Route::post('/programs', function () {
            // Store new program
        })->name('programs.store');
        Route::put('/programs/{id}', function ($id) {
            // Update program
        })->name('programs.update');
        Route::delete('/programs/{id}', function ($id) {
            // Delete program
        })->name('programs.destroy');

        // Trainees Management
        Route::get('/trainees', function () {
            return Inertia::render('Admin/Trainees');
        })->name('trainees');
        Route::post('/trainees', function () {
            // Store new trainee
        })->name('trainees.store');
        Route::put('/trainees/{id}', function ($id) {
            // Update trainee
        })->name('trainees.update');
        Route::delete('/trainees/{id}', function ($id) {
            // Delete trainee
        })->name('trainees.destroy');
        Route::patch('/trainees/{id}/status', function ($id) {
            // Update trainee status
        })->name('trainees.status');

        // Trainers Management
        Route::get('/trainers', function () {
            return Inertia::render('Admin/Trainers');
        })->name('trainers');
        Route::post('/trainers', function () {
            // Store new trainer
        })->name('trainers.store');
        Route::put('/trainers/{id}', function ($id) {
            // Update trainer
        })->name('trainers.update');
        Route::delete('/trainers/{id}', function ($id) {
            // Delete trainer
        })->name('trainers.destroy');

        // Payments Management
        Route::get('/payments', function () {
            return Inertia::render('Admin/Payments');
        })->name('payments');
        Route::post('/payments', function () {
            // Store new payment
        })->name('payments.store');
        Route::put('/payments/{id}', function ($id) {
            // Update payment
        })->name('payments.update');
        Route::delete('/payments/{id}', function ($id) {
            // Delete payment
        })->name('payments.destroy');
        Route::patch('/payments/{id}/status', function ($id) {
            // Update payment status
        })->name('payments.status');

        // Training Results Management
        Route::get('/trainings', function () {
            return Inertia::render('Admin/TrainingResults');
        })->name('trainings');
        Route::post('/trainings', function () {
            // Store new training result
        })->name('trainings.store');
        Route::put('/trainings/{id}', function ($id) {
            // Update training result
        })->name('trainings.update');
        Route::delete('/trainings/{id}', function ($id) {
            // Delete training result
        })->name('trainings.destroy');

        // Assessment Management
        Route::get('/assessments', function () {
            return Inertia::render('Admin/AssestmentResults');
        })->name('assessments');
        Route::post('/assessments', function () {
            // Store new assessment
        })->name('assessments.store');
        Route::put('/assessments/{id}', function ($id) {
            // Update assessment
        })->name('assessments.update');
        Route::delete('/assessments/{id}', function ($id) {
            // Delete assessment
        })->name('assessments.destroy');

        // Employment & Referrals Management
        Route::get('/employments', function () {
            return Inertia::render('Admin/EmploymentsReferrals');
        })->name('employments');
        Route::post('/employments', function () {
            // Store new employment record
        })->name('employments.store');
        Route::put('/employments/{id}', function ($id) {
            // Update employment record
        })->name('employments.update');
        Route::delete('/employments/{id}', function ($id) {
            // Delete employment record
        })->name('employments.destroy');

        // Reports & Analytics
        Route::get('/reports', function () {
            return Inertia::render('Admin/Reports');
        })->name('reports');
        Route::post('/reports/generate', function () {
            // Generate report
        })->name('reports.generate');
        Route::post('/reports/export', function () {
            // Export report
        })->name('reports.export');

        // User Management
        Route::get('/users', function () {
            return Inertia::render('Admin/Users');
        })->name('users');
        Route::post('/users', function () {
            // Store new user
        })->name('users.store');
        Route::put('/users/{id}', function ($id) {
            // Update user
        })->name('users.update');
        Route::delete('/users/{id}', function ($id) {
            // Delete user
        })->name('users.destroy');
        Route::patch('/users/{id}/status', function ($id) {
            // Update user status
        })->name('users.status');

        // Additional Admin Routes
        Route::get('/analytics', function () {
            // Analytics dashboard
        })->name('analytics');
        Route::get('/settings', function () {
            // System settings
        })->name('settings');
        Route::post('/backup', function () {
            // Create system backup
        })->name('backup');
    });




    // Officer routes with role-based access control
    Route::prefix('officer')->name('officer.')->middleware('role:officer')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Officer/Dashboard');
        })->name('dashboard');
        
        Route::get('/programs', function () {
            return Inertia::render('Officer/Courses');
        })->name('programs');
        
        Route::get('/trainees', function () {
            return Inertia::render('Officer/Trainees');
        })->name('trainees');
        
        Route::get('/trainers', function () {
            return Inertia::render('Officer/Trainers');
        })->name('trainers');

        // Officer can manage training schedules and results
        Route::get('/schedules', function () {
            return Inertia::render('Officer/Schedules');
        })->name('schedules');
        
        Route::get('/results', function () {
            return Inertia::render('Officer/Results');
        })->name('results');
    });
    // Cashier routes with role-based access control
    Route::prefix('cashier')->name('cashier.')->middleware('role:cashier')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Cashier/Dashboard');
        })->name('dashboard');
        
        Route::get('/payments', function () {
            return Inertia::render('Cashier/Payments');
        })->name('payments');
        
        Route::get('/receipts', function () {
            return Inertia::render('Cashier/Receipts');
        })->name('receipts');

        // Cashier specific payment operations
        Route::post('/payments/process', function () {
            // Process payment
        })->name('payments.process');
        
        Route::get('/reports/financial', function () {
            // Financial reports for cashier
        })->name('reports.financial');
    });

});

// Profile management routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
