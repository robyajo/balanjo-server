<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Facades\Activity;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class UserController extends Controller
{
    /**
     * Get list User
     *
     * @return JsonResponse
     */
    public function index()
    {
        try {
            $users = User::with(['roles'])->orderBy('id', 'desc')->get();
            $formattedUsers = $users->map(function ($user) {
                return $this->formatUserResponse($user);
            });
            activity()
                ->causedBy(Auth::user())
                ->event('show user')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  show user.");
            return response()->json([
                'message' => 'List User',
                'data' => $formattedUsers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }



    /**
     * Get detail User
     *
     * @return JsonResponse
     */
    public function show(string $uuid)
    {
        try {
            $user = User::with(['roles'])->where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->event('show user')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  show user.");
            return response()->json([
                'message' => 'Detail User',
                'data' => $this->formatUserResponse($user),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update User
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, string $uuid)
    {
        try {
            /** @var \App\Models\User $user */
            $user = User::with(['roles'])->where('uuid', $uuid)->first();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'name.required' => 'Name is required.',
                'name.max' => 'Name must not exceed 255 characters.',
                'email.required' => 'Email is required.',
                'email.email' => 'Email format is invalid.',
                'email.unique' => 'Email is already taken.',
                'phone.max' => 'Phone must not exceed 20 characters.',
                'address.max' => 'Address must not exceed 255 characters.',
                'city.max' => 'City must not exceed 100 characters.',
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

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldAvatarPath = public_path('assets/images/user/avatar/' . $user->avatar);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                $user->avatar = $this->uploadAvatar($request->file('avatar'));
            }
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('update')
                ->withProperties([
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} updated profile.");

            // Update user data
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
            ]);

            $roleName = $user->roles->first()->name ?? null;

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => [
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
                    'updated_at' => $user->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function destroy(string $uuid)
    {
        try {
            $user = User::with(['roles'])->where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }
            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->event('delete')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} deleted.");
            $user->delete();
            return response()->json([
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }
    /**
     * Deactivate user account
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivate(Request $request, string $uuid)
    {
        try {
            /** @var \App\Models\User $user */
            $user = User::with(['roles'])->where('uuid', $uuid)->first();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required',
                'reason' => 'nullable|string|max:500',
            ], [
                'password.required' => 'Password is required to deactivate account.',
                'reason.max' => 'Reason must not exceed 500 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Password is incorrect',
                ], 422);
            }

            // Deactivate account
            $user->update(['active' => 'inactive']);

            // Optional: Log deactivation reason
            // You can save the reason in a separate table

            // Logout user
            Auth::logout();
            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->event('deactivate')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} deactivated account.");
            return response()->json([
                'message' => 'Account deactivated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
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
        $path = public_path('assets/images/user/avatar/');

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
