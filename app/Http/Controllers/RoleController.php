<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Get list Role
     *
     * @return JsonResponse
     */
    public function index()
    {
        $data = Role::orderBy('id', 'desc')
            ->select('id', 'name')
            ->get();
        try {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($data)
                ->event('show role')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  show role.");
            return response()->json([
                'message' => 'List Role',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }
    /**
     * Get detail Role
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $data = Role::find($id)
            ->select('id', 'name')
            ->first();
        if (!$data) {
            return response()->json([
                'message' => 'Role not found',
            ], 404);
        }
        try {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($data)
                ->event('show role')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  show role.");
            return response()->json([
                'message' => 'Detail Role',
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created Role.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:roles,name|string|max:255',
                'permissions' => 'required|array',
            ],
            [
                'name.required' => 'Name role required.',
                'name.unique' => 'Name role must be unique.',
                'name.string' => 'Name role must be string.',
                'name.max' => 'Name role must be less than 255 characters.',
                'permissions.required' => 'Permissions role required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));
        try {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($role)
                ->event('create role')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  create role.");
            return response()->json([
                'message' => 'Role created successfully',
                'data' => $role,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified Role.
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:roles,name|string|max:255',
                'permissions' => 'required|array',
            ],
            [
                'name.required' => 'Name role required.',
                'name.unique' => 'Name role must be unique.',
                'name.string' => 'Name role must be string.',
                'name.max' => 'Name role must be less than 255 characters.',
                'permissions.required' => 'Permissions role required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
            ], 404);
        }
        $role->update(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));
        try {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($role)
                ->event('update role')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  update role.");
            return response()->json([
                'message' => 'Role updated successfully',
                'data' => $role,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified Role.
     *
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $data = Role::find($id);
        if (!$data) {
            return response()->json([
                'message' => 'Role not found',
            ], 404);
        }
        try {
            $data->delete();
            activity()
                ->causedBy(Auth::user())
                ->performedOn($data)
                ->event('delete role')
                ->withProperties([
                    'ip' => request()->ip(),
                    'date' => now(),
                    'device' => request()->userAgent(),
                ])
                ->log("User  delete role.");
            return response()->json([
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Server error: ' . $th->getMessage(),
            ], 500);
        }
    }
}
