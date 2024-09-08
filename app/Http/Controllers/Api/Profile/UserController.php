<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getMe()
    {
        $user = auth()->user();

        $roles = $user->roles()->pluck('title')->toArray();

        return response()->json([
            'user' => [
                'data' => $user,
                'roles' => $roles,
            ]
        ]);
    }
}
