<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    /**
     * Get list permission
     *
     * @return JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        try {
            $data = Permission::orderBy('id', 'desc')
                ->select('id', 'name')
                ->paginate(10);
            activity()
                ->event('get permission')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  get permission.");
            return response()->json([
                'message' => 'List Permission',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created permission.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:permissions,name|string|max:255',
            ],
            [
                'name.required' => 'Name permission required.',
                'name.unique' => 'Name permission must be unique.',
                'name.string' => 'Name permission must be string.',
                'name.max' => 'Name permission must be less than 255 characters.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $data = Permission::create(['name' => $request->name]);
            activity()
                ->event('create permission')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  create permission.");
            return response()->json([
                'message' => 'Permission created successfully',
                'data' => $data,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified permission.
     *
     * @return JsonResponse
     */
    public function show(string $id)
    {
        try {
            $data = Permission::find($id)
                ->select('id', 'name')
                ->first();
            if (!$data) {
                return response()->json([
                    'message' => 'Permission not found',
                ], 404);
            }
            $user = Auth::user();
            activity()
                ->event('show permission')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  show permission.");
            return response()->json([
                'message' => 'Detail Permission',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified permission.
     *
     * @return JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:permissions,name|string|max:255',
            ],
            [
                'name.required' => 'Name permission required.',
                'name.unique' => 'Name permission must be unique.',
                'name.string' => 'Name permission must be string.',
                'name.max' => 'Name permission must be less than 255 characters.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $data = Permission::find($id);
            if (!$data) {
                return response()->json([
                    'message' => 'Permission not found',
                ], 404);
            }
            $user = Auth::user();
            $data->update(['name' => $request->name]);
            activity()
                ->event('update permission')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  update permission.");
            return response()->json([
                'message' => 'Permission updated successfully',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified permission.
     *
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $data = Permission::find($id);
            if (!$data) {
                return response()->json([
                    'message' => 'Permission not found',
                ], 404);
            }
            $user = Auth::user();
            $data->delete();
            activity()
                ->event('delete permission')
                ->withProperties([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  delete permission.");
            return response()->json([
                'message' => 'Permission deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }
}
