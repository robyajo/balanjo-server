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

Route::middleware(AddCspHeaders::class)->group(function () {
    // Routes go here...
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('login', 'login')->name('login');
        Route::post('register', 'register');
    });


    Route::get('/email/verify', function (Request $request) {
        return response()->json([
            'message' => 'Please verify your email address.'
        ]);
    })->middleware('auth:sanctum')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
        // Find user manually since EmailVerificationRequest has issues with soft deletes
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Invalid verification link or user not found.'
            ], 404);
        }

        // Verify the hash manually
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 403);
        }

        // Check if already verified
        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 400);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Create a token for the user after successful verification
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ]
        ]);
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {

        $request->user()->sendEmailVerificationNotification();
        return response()->json([
            'message' => 'Verification link sent!'
        ]);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');





    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Auth routes
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout');
            Route::get('permission', 'permission');
            Route::post('forgot-password', 'forgotPassword');
            Route::post('refresh', 'refresh');
            Route::get('me', 'me');
        });
        // Log Activity routes
        Route::prefix('log-activity')->controller(LogActivityController::class)->group(function () {
            Route::get('/', 'index')->middleware('role:Super Admin');
            Route::get('show/{id}', 'show');
            Route::delete('destroy/{id}', 'destroy')->middleware('role:Super Admin');
            Route::get('user-activity', 'activity');
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
            Route::post('/update-profile/{uuid}', 'update');
            Route::post('/update-password', 'updatePassword');
            Route::delete('/avatar', 'deleteAvatar');
            Route::get('/activity', 'activity');
            Route::post('/deactivate', 'deactivate');
        });
    });
});
