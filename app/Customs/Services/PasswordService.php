<?php

namespace App\Customs\Services;

class PasswordService
{

    private function validatePassword($current_password)
    {
        if (!password_verify($current_password, auth()->user()->password)) {
            response()->json([
                'status' => 'failed',
                'message' => "Password didn't match the current password",
            ])->send();
            exit;
        }
    }

    public function changePassword($data)
    {
        $this->validatePassword($data['current_password']);
        $updatePassword = auth()->user()->update([
            'password' => bcrypt($data['password'])
        ]);

        if ($updatePassword) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Password updated successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred while updating password'
            ]);
        }
    }
}
