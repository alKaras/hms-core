<?php

namespace App\Http\Controllers\Api\Auth;

use App\Customs\Services\EmailVerificationService;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Stripe\Customer;
use Stripe\Stripe;

class AuthController extends Controller
{

    public function __construct(private EmailVerificationService $emailVerificationService)
    {
    }

    /**
     * Login method
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
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

        $token = auth()->attempt(['email' => $request->email, 'password' => $request->password]);
        if ($token) {
            $user = auth()->user();
            $roles = $user->roles()->orderBy('priority', 'desc')->pluck('title')->first();
            return $this->responseWithToken($token, $user, $roles);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'An error occurred while trying to login'
        ], 401);
    }

    /**
     * Registration method
     */

    public function register(Request $request)
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

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $customer = Customer::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'name' => "{$request->name} {$request->surname}",
        ]);

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'stripe_customer_id' => $customer->id,
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

            $this->emailVerificationService->sendVerificationLink($user);
            $token = auth()->login($user);
            return $this->responseWithToken($token, $user);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'An error occurred while trying to create user'
        ], 500);
    }

    /**
     * Show Email verification page
     */
    public function showVerificationPage(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        return view('verification', compact('token', 'email'));
    }

    /**
     * Verify User Email
     */
    public function verifyUserEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        return $this->emailVerificationService->verifyEmail($request->email, $request->token);

    }

    /**
     * Resend verification link
     */
    public function resendEmailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        return $this->emailVerificationService->resendLink($request->email);
    }

    /**
     * Logout method
     */

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'User has been logged out successfully',
        ]);
    }

    /**
     * Return JWT access token
     */
    protected function responseWithToken($token, $user, $roles = null)
    {
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'roles' => $roles ?? null,
            'access_token' => $token,
            'type' => 'bearer'
        ]);
    }
}
