<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LogActivityController extends Controller
{
    /**
     * Get all activities
     *
     * @return JsonResponse
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $activities = ModelsActivity::orderBy('created_at', 'desc')->paginate(10);
            return response()->json([
                'message' => 'Activities retrieved successfully',
                'activities' => $activities,
            ], 200);
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('show-all-activity')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} show activity.");
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Get all activities by user
     *
     * @return JsonResponse
     */
    public function activity()
    {
        try {
            $user = Auth::user();
            $activities = ModelsActivity::where('causer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            return response()->json([
                'message' => 'Activities retrieved successfully',
                'activities' => $activities,
            ], 200);
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('show-all-activity')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} show activity.");
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Get activity by id
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id)
    {
        try {
            $activities = ModelsActivity::findOrFail($id);
            return response()->json([
                'message' => 'Activity retrieved successfully',
                'activity' => $activities,
            ], 200);
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('show-activity')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} show activity.");
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Delete activity by id
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $activities = ModelsActivity::findOrFail($id);
            $activities->delete();
            return response()->json([
                'message' => 'Activity deleted successfully',
                'activity' => $activities,
            ], 200);
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('delete-activity')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User {$user->name} delete activity.");
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
