<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\Activitylog\Models\Activity as ModelsActivity;

class AuthController extends Controller
{
    /**
     * Get user permissions
     * 
     * @return JsonResponse
     */
    public function permission(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $roleName = $user->roles->first()->name ?? null;

            if ($roleName === 'Super Admin') {
                $permissions = Permission::all(['id', 'name']);
            } else {
                $permissions = $user->getPermissionsViaRoles()
                    ->map(fn($p) => ['id' => $p->id, 'name' => $p->name]);
            }

            return response()->json([
                'permissions' => $permissions,
                'role' => $roleName,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Register new user
     * @unauthenticated
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'email.unique' => 'Email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'avatar.image' => 'Avatar must be an image.',
            'avatar.mimes' => 'Avatar must be jpeg, png, jpg, or gif.',
            'avatar.max' => 'Avatar size must not exceed 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Handle avatar upload
            if ($request->hasFile('avatar')) {

                $avatar = $request->file('avatar');
                $filename = time() . '.' . $avatar->getClientOriginalExtension();
                $image = Image::read($avatar);
                // Resize image
                $image->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path('assets/images/user/avatar/' . $filename));
                $avatarFilename = $filename;
            }
            // Create user
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'avatar' => $avatarFilename,
                'active' => 'active',
            ]);

            // Assign default role (optional)
            // $user->assignRole('User');

            // Generate token
            $token = Auth::login($user);
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('create')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} created an account.");

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $this->formatUserResponse($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     * @unauthenticated
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:3'
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 3 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('auth-token')->plainTextToken;
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('login')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} logged in.");
            $activity = ModelsActivity::all()->last();

            $activity->description;
            $activity->changes();

            return response()->json([
                'message' => 'Login successful',
                'activity' => $activity,
                'data' => $this->formatUserResponse($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get authenticated user data
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();
            activity()
                ->causedBy($user)
                ->event('me')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} get data.");
            $activity = ModelsActivity::all()->last();

            $activity->description;
            $activity->changes([
                'attributes' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'old' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            return response()->json([
                'activity' => $activity,
                'data' => $this->formatUserResponse($user),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Forgot password
     * @unauthenticated
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email format is invalid.',
            'email.exists' => 'Email is not registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            activity()
                ->causedBy($user)
                ->withProperties(['email' => $user->email])
                ->log("User {$user->name} created an account.");

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'Password updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Logout user
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            Auth::logout();
            return response()->json(['message' => 'User logged out successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Refresh authentication token
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::refresh();
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to refresh token: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload and resize avatar image
     *
     * @param UploadedFile $avatar
     * @return string
     */
    private function uploadAvatar($avatar)
    {
        $filename = time() . '_' . Str::random(10) . '.' . $avatar->getClientOriginalExtension();
        $path = public_path('images/auth/icons/avatars/');

        // Create directory if not exists
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $image = Image::read($avatar);
        $image->resize(300, 300, function ($constraint) {
            $constraint->aspectRatio();
        })->save($path . $filename);

        return $filename;
    }

    /**
     * Format user response data
     *
     * @param User $user
     * @return array
     */
    private function formatUserResponse($user)
    {
        $roleName = $user->roles->first()->name ?? null;

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar
                ? asset('assets/images/user/avatar/' . $user->avatar)
                : null,
            'role' => $roleName,
            'active' => $user->active,
            'created_at' => $user->created_at,
        ];
    }
}
