<?php

namespace App\Http\Controllers;

use App\Customs\Services\NotificationService\NotificationService;
use App\Http\Resources\UserReferralResource;
use App\Models\HServices;
use App\Models\User\User;
use App\Models\User\UserReferral;
use App\Notifications\UserReferralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserReferralController extends Controller
{

    public function __construct(private NotificationService $notificationService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? null;
        $query = UserReferral::where("user_id", $userId)
            ->where('expired_at', '>', now());

        if ($limit) {
            $referrals = $query->limit($limit)->get();
        } else {
            $referrals = $query->get();
        }

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
        $user = User::find($request->user_id);

        $services = HServices::whereIn('id', $request->service_id)->get();

        $decodedData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
            ],
            'services' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'department' => $service->department->content->title,
                ];
            })
        ];

        $encodedData = base64_encode(json_encode($decodedData));
        $referralCode = $this->generateCode();

        $userReferral = UserReferral::create([
            'user_id' => $user->id,
            'referral_code' => $referralCode,
            'encoded_data' => $encodedData,
            'expired_at' => now()->addYear(),
        ]);

        $this->notificationService->sendReferral($user, $referralCode);
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
                'status' => 'error',
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
                'status' => 'error',
                'message' => "No user referrals for provided id $id",
            ]);
        }

        $referral->delete();

        return response()->json([
            'status' => 'ok',
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
