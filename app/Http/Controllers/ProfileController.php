<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneHelper;
use App\Models\User;
use App\Rules\UniqueIndonesianPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    /**
     * Get authenticated user profile
     * 
     * @return JsonResponse
     */
    public function index()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $roleName = $user->roles->first()->name ?? null;

            return response()->json([
                'message' => 'User profile retrieved successfully',
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
                        ? url('assets/images/user/avatar/' . $user->avatar)
                        : null,
                    'role' => $roleName,
                    'active' => $user->active,
                    'profile' => $user->profile,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
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
     * Update user profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Trim dan sanitize input
            $data = $request->all();
            if (isset($data['email'])) {
                $data['email'] = trim(strtolower($data['email']));
            }
            if (isset($data['phone'])) {
                $data['phone'] = trim($data['phone']);
            }

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($user->id, 'id')
                ],
                'phone' => ['nullable', 'string', 'max:15', new UniqueIndonesianPhone($user->id)],
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
                'phone.unique' => 'Phone is already taken.',
                'address.max' => 'Address must not exceed 255 characters.',
                'city.max' => 'City must not exceed 100 characters.',
                'avatar.image' => 'Avatar must be an image.',
                'avatar.mimes' => 'Avatar must be jpeg, png, jpg, or gif.',
                'avatar.max' => 'Avatar size must not exceed 2MB.',
            ]);

            if ($validator->fails()) {
                // Debug validation errors
                Log::error('Profile Update Validation Failed', [
                    'user_id' => $user->id,
                    'current_email' => $user->email,
                    'request_email' => $request->email,
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }

            $phone = PhoneHelper::formatToIndonesian($request->phone);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    $oldAvatarPath = storage_path('app/public/assets/images/user/avatar/' . $user->avatar);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
                $user->avatar = $this->uploadAvatar($request->file('avatar'));
            }

            // Update user data PERTAMA
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $phone,
                'address' => $request->address,
                'city' => $request->city,
            ]);

            // Activity log SETELAH update
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

            // Check profile completion
            $allFieldsFilled = $user->name &&
                $user->email &&
                $user->phone &&
                $user->address &&
                $user->city;

            if ($allFieldsFilled && $user->profile !== 'active') {
                $user->update(['profile' => 'active']);
                $user->refresh();
            }

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
                        ? url('assets/images/user/avatar/' . $user->avatar)
                        : null,
                    'role' => $roleName,
                    'active' => $user->active,
                    'profile' => $user->profile,
                    'updated_at' => $user->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Profile Update Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed|different:current_password',
            ], [
                'current_password.required' => 'Current password is required.',
                'new_password.required' => 'New password is required.',
                'new_password.min' => 'New password must be at least 6 characters.',
                'new_password.confirmed' => 'New password confirmation does not match.',
                'new_password.different' => 'New password must be different from current password.',
            ]);
            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',

                ], 422);
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }


            // Update password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);
            // Activity log SETELAH update
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('update-password')
                ->withProperties([
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} updated password.");
            return response()->json([
                'message' => 'Password updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user avatar
     * 
     * @return JsonResponse
     */
    public function deleteAvatar()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            if (!$user->avatar) {
                return response()->json([
                    'message' => 'No avatar to delete',
                ], 404);
            }

            // Delete avatar file
            $avatarPath = storage_path('app/public/assets/images/user/avatar/' . $user->avatar);
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }

            // Update user avatar to null
            $user->update(['avatar' => null]);

            return response()->json([
                'message' => 'Avatar deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity log (optional)
     * 
     * @return JsonResponse
     */
    public function activity()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // You can implement activity log here
            // Example: using spatie/laravel-activitylog package

            return response()->json([
                'message' => 'Activity log feature not implemented yet',
                'user' => [
                    'last_login' => $user->updated_at,
                    'account_created' => $user->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate user account
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function deactivate(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

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
        $path = storage_path('app/public/assets/images/user/avatar/');

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
}
