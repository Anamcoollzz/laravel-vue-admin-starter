<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/export', [UserManagementController::class, 'export'])->name('export');
        Route::post('/import', [UserManagementController::class, 'import'])->name('import');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->name('index');
        Route::get('/export', [RoleManagementController::class, 'export'])->name('export');
        Route::post('/import', [RoleManagementController::class, 'import'])->name('import');
        Route::post('/', [RoleManagementController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleManagementController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionManagementController::class, 'index'])->name('index');
        Route::get('/export', [PermissionManagementController::class, 'export'])->name('export');
        Route::post('/import', [PermissionManagementController::class, 'import'])->name('import');
        Route::post('/', [PermissionManagementController::class, 'store'])->name('store');
        Route::put('/{permission}', [PermissionManagementController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionManagementController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';
