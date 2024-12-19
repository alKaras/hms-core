<?php

namespace App\Http\Controllers\Api\Profile;

use App\Customs\Services\NotificationService\NotificationService;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\MedCard;
use App\Models\User\User;
use Illuminate\Http\Request;
use App\Models\User\UserRole;
use App\Models\Hospital\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Notifications\RegisteredUserCredentials;

class UserController extends Controller
{

    public function __construct(private NotificationService $notificationService)
    {
    }

    /**
     * Get logged in user method
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getMe()
    {
        $user = auth()->user();

        //        $roles = $user->roles()->pluck('title')->first();
        $highestPriorityRole = $user->roles()->orderBy('priority', 'desc')->pluck('title')->first();
        $userRecord = User::find($user->id);

        $medcard = MedCard::where('user_id', $userRecord->id)->first();

        if ($highestPriorityRole === 'doctor' || $highestPriorityRole === 'manager') {
            $hospitalId = Hospital::find($user->hospital_id) ?? null;
            return response()->json([
                'user' => [
                    'data' => $user,
                    'roles' => $highestPriorityRole,
                    'id' => $user->id,
                    'hospitalId' => $hospitalId->id ?? null,
                    'doctor' => $highestPriorityRole === 'doctor' ? $userRecord->doctor?->id : null,
                    'medcard' => null,
                ]
            ]);
        } else {
            return response()->json([
                'user' => [
                    'data' => $user,
                    'roles' => $highestPriorityRole,
                    'hospitalId' => null,
                    'id' => $user->id,
                    'medcard' => $medcard->id ?? null,
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
            ],
            'hospital_id' => ['exists:hospital,id', 'nullable'],
        ]);

        $isManager = $request->input('isManager', false);

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
            'hospital_id' => $isManager ? $request->hospital_id : null,
            'email_verified_at' => $isManager ? Carbon::now() : null,
        ]);

        $userRole = Role::where('title', 'user')->value('id');

        if ($user) {
            if ($isManager) {
                $managerRole = Role::where('title', 'manager')->value('id');

                $user->roles()->attach($userRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $user->roles()->attach($managerRole, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $userRole,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->notificationService->sendCredentials($user, $request->password);

            return new UserResource($user);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while trying to create user'
            ], 500);
        }
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
                'status' => 'error',
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
                'status' => 'error',
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
                'status' => 'error',
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
                'status' => 'error',
                'message' => 'Role not found'
            ], 404);
        }

        $existedRole = UserRole::where('user_id', $user_id)
            ->where('role_id', $role->id)
            ->exists();

        if ($existedRole) {
            return response()->json([
                'status' => 'error',
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
                    'status' => 'error',
                    'message' => 'Role not found',
                ], 404);
            }
        }
        return response()->json([
            'status' => 'error',
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
                'status' => 'error',
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
