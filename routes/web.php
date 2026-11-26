<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DatabaseExplorerController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\LaravelLogViewerController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\RequestLogController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::post('/', [MessageController::class, 'store'])->name('store');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    });

    Route::prefix('announcements')->name('announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->middleware('permission:announcements.view')->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->middleware('permission:announcements.create')->name('store');
        Route::put('/{announcement}', [AnnouncementController::class, 'update'])->middleware('permission:announcements.edit')->name('update');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.delete')->name('destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('/export', [UserManagementController::class, 'export'])->middleware('permission:users.view')->name('export');
        Route::get('/import-template', [UserManagementController::class, 'template'])->middleware('permission:users.create')->name('import-template');
        Route::post('/import', [UserManagementController::class, 'import'])->middleware('permission:users.create')->name('import');
        Route::post('/', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.edit')->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('index');
        Route::get('/export', [RoleManagementController::class, 'export'])->middleware('permission:roles.view')->name('export');
        Route::get('/import-template', [RoleManagementController::class, 'template'])->middleware('permission:roles.create')->name('import-template');
        Route::post('/import', [RoleManagementController::class, 'import'])->middleware('permission:roles.create')->name('import');
        Route::post('/', [RoleManagementController::class, 'store'])->middleware('permission:roles.create')->name('store');
        Route::put('/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.edit')->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionManagementController::class, 'index'])->middleware('permission:permissions.view')->name('index');
        Route::get('/export', [PermissionManagementController::class, 'export'])->middleware('permission:permissions.view')->name('export');
        Route::get('/import-template', [PermissionManagementController::class, 'template'])->middleware('permission:permissions.create')->name('import-template');
        Route::post('/import', [PermissionManagementController::class, 'import'])->middleware('permission:permissions.create')->name('import');
        Route::post('/', [PermissionManagementController::class, 'store'])->middleware('permission:permissions.create')->name('store');
        Route::put('/{permission}', [PermissionManagementController::class, 'update'])->middleware('permission:permissions.edit')->name('update');
        Route::delete('/{permission}', [PermissionManagementController::class, 'destroy'])->middleware('permission:permissions.delete')->name('destroy');
    });

    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [DatabaseBackupController::class, 'index'])->middleware('permission:backups.view')->name('index');
        Route::post('/', [DatabaseBackupController::class, 'store'])->middleware('permission:backups.create')->name('store');
        Route::get('/{filename}/download', [DatabaseBackupController::class, 'download'])->middleware('permission:backups.view')->name('download');
        Route::delete('/{filename}', [DatabaseBackupController::class, 'destroy'])->middleware('permission:backups.delete')->name('destroy');
    });

    Route::prefix('database')->name('database.')->group(function () {
        Route::get('/', [DatabaseExplorerController::class, 'index'])->middleware('permission:database.view')->name('index');
        Route::get('/{table}', [DatabaseExplorerController::class, 'show'])->middleware('permission:database.view')->name('show');
        Route::patch('/{table}/columns/{column}', [DatabaseExplorerController::class, 'updateColumn'])->middleware('permission:database.edit')->name('columns.update');
        Route::delete('/{table}/columns/{column}', [DatabaseExplorerController::class, 'dropColumn'])->middleware('permission:database.delete')->name('columns.drop');
        Route::put('/{table}/rows/{id}', [DatabaseExplorerController::class, 'updateRow'])->middleware('permission:database.edit')->name('rows.update');
        Route::delete('/{table}/rows/{id}', [DatabaseExplorerController::class, 'deleteRow'])->middleware('permission:database.delete')->name('rows.delete');
    });

    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/', [FileManagerController::class, 'index'])->middleware('permission:files.view')->name('index');
        Route::post('/upload', [FileManagerController::class, 'upload'])->middleware('permission:files.create')->name('upload');
        Route::post('/folder', [FileManagerController::class, 'createFolder'])->middleware('permission:files.create')->name('folder');
        Route::get('/download', [FileManagerController::class, 'download'])->middleware('permission:files.view')->name('download');
        Route::delete('/', [FileManagerController::class, 'destroy'])->middleware('permission:files.delete')->name('destroy');
    });

    Route::prefix('request-logs')->name('request-logs.')->group(function () {
        Route::get('/', [RequestLogController::class, 'index'])->middleware('permission:logs.view')->name('index');
        Route::delete('/clear', [RequestLogController::class, 'clear'])->middleware('permission:logs.delete')->name('clear');
        Route::delete('/{requestLog}', [RequestLogController::class, 'destroy'])->middleware('permission:logs.delete')->name('destroy');
    });

    Route::prefix('laravel-logs')->name('laravel-logs.')->group(function () {
        Route::get('/', [LaravelLogViewerController::class, 'index'])->middleware('permission:logs.view')->name('index');
        Route::get('/download', [LaravelLogViewerController::class, 'download'])->middleware('permission:logs.view')->name('download');
        Route::post('/clear', [LaravelLogViewerController::class, 'clear'])->middleware('permission:logs.delete')->name('clear');
        Route::delete('/', [LaravelLogViewerController::class, 'destroy'])->middleware('permission:logs.delete')->name('destroy');
    });

    Route::prefix('app-settings')->name('app-settings.')->group(function () {
        Route::get('/', [AppSettingController::class, 'index'])->middleware('permission:settings.view')->name('index');
        Route::post('/general', [AppSettingController::class, 'updateGeneral'])->middleware('permission:settings.edit')->name('general');
        Route::put('/smtp', [AppSettingController::class, 'updateSmtp'])->middleware('permission:settings.edit')->name('smtp');
        Route::put('/privacy', [AppSettingController::class, 'updatePrivacy'])->middleware('permission:settings.edit')->name('privacy');
        Route::put('/security', [AppSettingController::class, 'updateSecurity'])->middleware('permission:settings.edit')->name('security');
    });
});

require __DIR__.'/settings.php';
