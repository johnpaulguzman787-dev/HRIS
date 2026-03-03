<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard route - UPDATED to use admin_dashboard
Route::get('/dashboard', [DashboardController::class, 'admin_dashboard'])->name('dashboard');

// Employee routes
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/directory', [EmployeeController::class, 'directory'])->name('directory');
});

// Optional: Add more dashboard routes kung kailangan
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin_dashboard'])->name('admin');
    Route::get('/user', [DashboardController::class, 'user_dashboard'])->name('user');
});

// Fallback route for 404 errors (optional)
Route::fallback(function () {
    return redirect()->route('dashboard');
});