<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Hospital\Hospital;
use App\Models\Role;
use App\Models\User\User;
use App\Models\User\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get logged in user method
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getMe()
    {
        $user = auth()->user();

        //        $roles = $user->roles()->pluck('title')->first();
        $highestPriorityRole = $user->roles()->orderBy('priority', 'desc')->pluck('title')->first();
        if ($highestPriorityRole === 'doctor') {
            $hospitalId = Hospital::find($user->doctor->hospital_id)->first() ?? null;
            return response()->json([
                'user' => [
                    'data' => $user,
                    'roles' => $highestPriorityRole,
                    'id' => $user->id,
                    'hospitalId' => $hospitalId->id ?? null,
                ]
            ]);
        } else if ($highestPriorityRole === 'manager') {
            $hospitalId = Hospital::find($user->hospital_id) ?? 0;
            return response()->json([
                'user' => [
                    'data' => $user,
                    'roles' => $highestPriorityRole,
                    'id' => $user->id,
                    'hospitalId' => $hospitalId->id ?? null,
                ]
            ]);
        } else {
            return response()->json([
                'user' => [
                    'data' => $user,
                    'roles' => $highestPriorityRole,
                    'id' => $user->id,
                ]
            ]);
        }

    }

    /**
     * Fetch all users method (user/fetch)
     */

    public function fetchAll(Request $request)
    {
        // $users = User::all();
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $users = User::paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Create a new user method (user/create)
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users'],
            'phone' => ['required', 'numeric'],
            'password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);

        $roleId = Role::where('title', 'user')->value('id');

        if ($user) {
            DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return new UserResource($user);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'An error occurred while trying to create user'
        ], 500);
    }


    /**
     * Show special user (user/fetch/{id})
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'failure',
                'message' => 'User is not registered or added',
            ], 404);
        }

        return new UserResource($user);
    }

    /**
     * Edit user information (user/edit/{id})
     */
    public function update(Request $request, $user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided user_id'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => ['string', 'max:255'],
            'surname' => ['string', 'max:255'],
            'email' => ['string', 'email', 'unique:users'],
            'phone' => ['numeric'],
            'active' => ['boolean'],
            'password' => [
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $user->update([
            'name' => $request->name ?? $user->name,
            'surname' => $request->surname ?? $user->surname,
            'email' => $request->email ?? $user->email,
            'phone' => $request->phone ?? $user->phone,
            'active' => $request->active ?? $user->active,
            'password' => !empty($request->password) ? bcrypt($request->password) : $user->password,
        ]);

        return new UserResource($user);
    }


    /**
     * Attach user role method (/user/attach-role/{id})
     * @param \Illuminate\Http\Request $request
     * @param mixed $user_id
     * @return JsonResponse|mixed
     */
    public function attachRole(Request $request, $user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'status' => 'failure',
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $role = Role::where('title', $request->role)->first();

        if (!$role) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Role not found'
            ], 404);
        }

        $existedRole = UserRole::where('user_id', $user_id)
            ->where('role_id', $role->id)
            ->exists();

        if ($existedRole) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Role is already attached to user',
            ]);
        }

        $user->roles()->attach($role->id, [
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Role Attached successfully'
        ]);
    }

    /**
     * Detach user role method (user/detach-role/{id})
     * @param \Illuminate\Http\Request $request
     * @param mixed $user_id
     * @return JsonResponse|mixed
     */
    public function detachRole(Request $request, $user_id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find($user_id);

        if ($user) {

            $role = Role::where('title', $request->role)->first();
            if ($role) {
                $user->roles()->detach($role->id);
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Role detached successfully',
                ]);
            } else {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Role not found',
                ], 404);
            }
        }
        return response()->json([
            'status' => 'failure',
            'message' => 'User not found',
        ], 404);
    }

    /**
     * Delete user method (user/delete/{id})
     * @param $user_id
     * @return JsonResponse|mixed
     */

    public function destroy($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'status' => 'failure',
                'message' => "Can\'t find any users by provided id #$user_id"
            ], 404);
        }

        $user->roles()->detach();
        $user->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'User and their roles deleted successfully'
        ]);
    }
}
