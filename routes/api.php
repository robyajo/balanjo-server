<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogActivityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Csp\AddCspHeaders;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware(AddCspHeaders::class)->group(function () {
    // Routes go here...
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
    });
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout');
            Route::post('permission', 'permission');
            Route::post('forgot-password', 'forgotPassword');
            Route::post('refresh', 'refresh');
            Route::get('me', 'me');
        });
        // Log Activity routes
        Route::prefix('log-activity')->controller(LogActivityController::class)->group(function () {
            Route::get('index', 'index')->middleware('role:Super Admin');
            Route::get('show/{id}', 'show')->middleware('role:Super Admin');
            Route::delete('destroy/{id}', 'destroy')->middleware('role:Super Admin');
            Route::get('activity', 'activity');
        });

        // User routes
        Route::middleware(['role:Super Admin|Admin'])->group(function () {
            Route::prefix('user')->controller(UserController::class)->group(function () {
                Route::get('index', 'index');
                Route::get('show/{uuid}', 'show');
                Route::put('update/{uuid}', 'update');
                Route::post('upload-avatar/{uuid}', 'uploadAvatar');
                Route::post('/deactivate', 'deactivate');
                Route::delete('destroy/{uuid}', 'destroy');
            });
        });

        // Admin routes
        Route::middleware(['role:Super Admin'])->group(function () {
            // Roles resource
            Route::prefix('role')->controller(RoleController::class)->group(function () {
                Route::get('index', 'index');
                Route::get('show/{id}', 'show');
                Route::post('store', 'store');
                Route::put('update/{id}', 'update');
                Route::delete('destroy/{id}', 'destroy');
            });

            // Permissions resource
            Route::prefix('permission')->controller(PermissionController::class)->group(function () {
                Route::get('index', 'index');
                Route::get('show/{id}', 'show');
                Route::post('store', 'store');
                Route::put('update/{id}', 'update');
                Route::delete('destroy/{id}', 'destroy');
            });
        });

        // Profile routes
        Route::prefix('profile')->controller(ProfileController::class)->group(function () {
            Route::get('/', 'index');
            Route::put('/update', 'update');
            Route::put('/password', 'updatePassword');
            Route::delete('/avatar', 'deleteAvatar');
            Route::get('/activity', 'activity');
            Route::post('/deactivate', 'deactivate');
        });
    });
});
