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
            $activities = ModelsActivity::all();
            return response()->json([
                'message' => 'Activities retrieved successfully',
                'activities' => $activities,
            ], 200);
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
            $activities = ModelsActivity::where('causer_id', $user->id)->get();
            return response()->json([
                'message' => 'Activities retrieved successfully',
                'activities' => $activities,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Get all activities by user
     *
     * @return JsonResponse
     */
    public function activityUserShow(string $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            $activities = ModelsActivity::where('causer_id', $user->id)->get();
            return response()->json([
                'message' => 'Activities retrieved successfully',
                'activities' => $activities,
            ], 200);
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
            $activity = ModelsActivity::find($id);
            return response()->json([
                'message' => 'Activity retrieved successfully',
                'activity' => $activity,
            ], 200);
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
            $activity = ModelsActivity::find($id);
            $activity->delete();
            return response()->json([
                'message' => 'Activity deleted successfully',
                'activity' => $activity,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
