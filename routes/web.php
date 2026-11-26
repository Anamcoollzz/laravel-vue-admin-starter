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
        Route::get('/', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('/export', [UserManagementController::class, 'export'])->middleware('permission:users.view')->name('export');
        Route::post('/import', [UserManagementController::class, 'import'])->middleware('permission:users.create')->name('import');
        Route::post('/', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.edit')->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('index');
        Route::get('/export', [RoleManagementController::class, 'export'])->middleware('permission:roles.view')->name('export');
        Route::post('/import', [RoleManagementController::class, 'import'])->middleware('permission:roles.create')->name('import');
        Route::post('/', [RoleManagementController::class, 'store'])->middleware('permission:roles.create')->name('store');
        Route::put('/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.edit')->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionManagementController::class, 'index'])->middleware('permission:permissions.view')->name('index');
        Route::get('/export', [PermissionManagementController::class, 'export'])->middleware('permission:permissions.view')->name('export');
        Route::post('/import', [PermissionManagementController::class, 'import'])->middleware('permission:permissions.create')->name('import');
        Route::post('/', [PermissionManagementController::class, 'store'])->middleware('permission:permissions.create')->name('store');
        Route::put('/{permission}', [PermissionManagementController::class, 'update'])->middleware('permission:permissions.edit')->name('update');
        Route::delete('/{permission}', [PermissionManagementController::class, 'destroy'])->middleware('permission:permissions.delete')->name('destroy');
    });
});

require __DIR__.'/settings.php';
