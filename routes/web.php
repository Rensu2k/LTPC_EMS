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
    Route::get('/officer/dashboard', function () {
        return Inertia::render('Officer/Dashboard');
    })->name('officer.dashboard');
    
    Route::get('/officer/courses', function () {
        return Inertia::render('Officer/Courses');
    })->name('officer.courses');
    
    Route::get('/officer/trainees', function () {
        return Inertia::render('Officer/Trainees');
    })->name('officer.trainees');
    
    Route::get('/officer/trainers', function () {
        return Inertia::render('Officer/Trainers');
    })->name('officer.trainers');

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
