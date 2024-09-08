<?php

namespace App\Http\Controllers\Api\Profile;

use App\Customs\Services\PasswordService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{

    public function __construct(private PasswordService $passwordService) {}

    public function changeUserPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
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

        return $this->passwordService->changePassword(['current_password' => $request->current_password, 'password' => $request->password]);
    }
}
