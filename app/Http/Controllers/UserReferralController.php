<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\HServices;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use App\Models\Department\Department;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserReferralResource;

class UserReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $referrals = UserReferral::where("user_id", $userId)
            ->where('expired_at', '>', now())->get();

        return UserReferralResource::collection($referrals);
    }

    /**
     * Create new user referral code and related data
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'service_ids.*' => ['required', 'exists:services,id'],
            'service_id' => ['required', 'array']
            // 'department_id' => ['required', 'exists:department,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $services = Hservices::whereIn('id', $request->service_id)->get();

        $decodedData = [
            'user' => [
                'id' => $request->user_id,
                'name' => User::find($request->user_id)->name,
                'surname' => User::find($request->user_id)->surname,
            ],
            'services' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'department' => $service->department->content->title,
                ];
            })
            // 'service_id' => $request->service_id,
            // 'service_name' => HServices::find($request->service_id)->name,
            // 'department' => [
            //     'id' => $request->department_id,
            //     'title' => Department::find($request->department_id)->content->title,
            // ]
        ];

        $encodedData = base64_encode(json_encode($decodedData));

        $userReferral = UserReferral::create([
            'user_id' => $request->user_id,
            'referral_code' => $this->generateCode(),
            'encoded_data' => $encodedData,
            'decoded_data' => json_encode($decodedData),
            'expired_at' => now()->addMonth(),
        ]);

        return new UserReferralResource($userReferral);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $referral = UserReferral::find($id);
        if (!$referral) {
            return response()->json([
                'status' => 'failure',
                'message' => "No user referrals for provided id $id",
            ]);
        }
        return new UserReferralResource($referral);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $referral = UserReferral::find($id);
        if (!$referral) {
            return response()->json([
                'status' => 'failure',
                'message' => "No user referrals for provided id $id",
            ]);
        }

        $referral->delete();

        return response()->json([
            'status' => 'success',
            'message'
        ]);
    }

    /**
     * Generate code method
     */
    private function generateCode()
    {
        $randomNumber = mt_rand(1000, 9999) . '-' .
            mt_rand(1000, 9999) . '-' .
            mt_rand(1000, 9999) . '-' .
            mt_rand(1000, 9999);

        return $randomNumber;
    }
}
