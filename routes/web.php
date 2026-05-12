<?php

use Illuminate\Support\Facades\Route;

// 1. Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication Routes (Rate Limited)
Route::middleware(['throttle:login'])->group(function () {
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('authenticate');
});

Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// Customer Registration Routes (Rate Limited)
Route::middleware(['throttle:registration'])->group(function () {
    Route::get('/register', [
        \App\Http\Controllers\CustomerRegistrationController::class,
        'showRegister'
    ])->name('register');
    Route::post('/register', [
        \App\Http\Controllers\CustomerRegistrationController::class,
        'register'
    ])->name('register.store');
});

// Protected Shopkeeper Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // General Staff Access
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->except(['destroy', 'edit', 'update']);

    // Admin Only Access
    Route::middleware('role:admin')->group(function () {
        Route::resource('customers', \App\Http\Controllers\CustomerController::class)->only(['edit', 'update', 'destroy']);
    });

    Route::post('/customers/{customer}/reminder', [\App\Http\Controllers\CustomerController::class, 'sendReminder'])->name('customers.reminder');
    Route::get('/customers/{customer}/statement', [\App\Http\Controllers\ReportController::class, 'downloadStatement'])->name('customers.statement');
    Route::post('/credits', [\App\Http\Controllers\CreditController::class, 'store'])->name('credits.store');
    Route::post('/credits/{credit}/repay', [
        \App\Http\Controllers\CreditController::class,
        'repayment'
    ])->name('credits.repay');
});

// Customer Portal Routes
Route::prefix('portal')->group(function () {
    Route::get('/login', [\App\Http\Controllers\PortalController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [\App\Http\Controllers\PortalController::class, 'login'])->name('portal.authenticate');
    Route::post('/logout', [\App\Http\Controllers\PortalController::class, 'logout'])->name('portal.logout');

    Route::middleware('auth.customer')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\PortalController::class, 'dashboard'])->name('portal.dashboard');
    });
});