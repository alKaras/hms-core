<?php

namespace App\Customs\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EmailVerificationService
{

    /**
     * Send Verification link to a user
     */

    public function sendVerificationLink(object $user)
    {
        Notification::send($user, new EmailVerificationNotification($this->generateVerificationLink($user->email)));
    }

    /**
     * Resend link with token
     */
    public function resendLink($email)
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->sendVerificationLink($user);

            return response()->json([
                'status' => 'success',
                'message' => 'verification link sent successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "User not found"
            ], 404);
        }
    }

    /**
     * Verify Token
     */

    protected function verifyToken($user, string $token)
    {
        //        $user = User::where('email', $email)->first();
//
//        if (!$user){
//            return response()->json([
//                'status' => 'failed',
//                'message' => "User not found"
//            ], 404);
//        }

        $token = $user->verificationToken()->where('token', $token)->first();

        if ($token) {
            if ($token->expired_at >= now()) {
                return $token;
            } else {
                $token->delete();
                response()->json([
                    'status' => 'failed',
                    'message' => 'Token expired',
                ], 400)->send();
                exit;
            }
        }
        response()->json([
            'status' => 'failed',
            'message' => 'Invalid token'
        ], 400)->send();
        exit;
    }

    /**
     * Verify user email
     */
    public function verifyEmail(string $email, string $token)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => "User not found"
            ], 404);
        }
        $this->checkIfEmailVerified($user);

        $verifiedToken = $this->verifyToken($user, $token);

        if ($verifiedToken instanceof \Illuminate\Http\JsonResponse) {
            return $verifiedToken;
        }

        if ($user->markEmailAsVerified()) {
            $verifiedToken->delete();
            response()->json([
                'status' => 'success',
                'message' => 'Email has been verified successfully'
            ])->send();
        } else {
            response()->json([
                'status' => 'failed',
                'message' => 'Email verification failed, please try again later'
            ], 500)->send();
        }


    }

    /**
     * Check if the user has already been verified
     */
    protected function checkIfEmailVerified($user)
    {
        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Email has already been verified'
            ], 400);
        }
    }

    /**
     * Generate verification link
     */
    protected function generateVerificationLink(string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            response()->json([
                'status' => 'failed',
                'message' => "User not found"
            ], 404)->send();
            exit;
        }

        $checkIfTokenExist = $user->verificationToken()->first();

        if ($checkIfTokenExist) {
            $checkIfTokenExist->delete();
        }
        $token = Str::uuid();

        $url = config('app.url') . "/verification?token=" . $token . "&email=" . $email;
        $saveToken = EmailVerificationToken::create([
            "user_id" => $user->id,
            'token' => $token,
            'expired_at' => now()->addMinutes(60),
        ]);
        if ($saveToken) {
            return $url;
        }
    }

}
