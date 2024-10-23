<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\HospitalReview;
use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\HospitalReviewResource;

class HospitalReviewController extends Controller
{
    /**
     * Displaying all resources by hospital Id
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'exists:hospital,id'],
            'limit' => ['numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => "HospitalId is not provided or there is no data for hospitalId#$request->hospital_id",
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->limit) {
            $hospitalReview = HospitalReview::where('hospital_id', $request->hospital_id)
                ->limit($request->limit)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $hospitalReview = HospitalReview::where('hospital_id', $request->hospital_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }



        return HospitalReviewResource::collection($hospitalReview);
    }

    /**
     * Create a new hospital review
     * @param \Illuminate\Http\Request $request
     * @return array|mixed|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'exists:hospital,id'],
            'rating' => ['required'],
            'body' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Provided data is incorrect',
                'error' => $validator->errors()
            ], 422);
        }
        $userId = $user->id;
        $hospitalId = $request->hospital_id;
        $rating = $request->rating;
        $body = $request->body;

        $hospitalReview = HospitalReview::create([
            'user_id' => $userId,
            'hospital_id' => $hospitalId,
            'rating' => $rating,
            'body' => $body
        ]);

        if ($hospitalReview) {
            return response()->json([
                'status' => 'success',
                'review' => new HospitalReviewResource($hospitalReview)
            ]);
        } else {
            return [];
        }
    }


    /**
     * Amount of reviews by specific hospital Id
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAmountOfReviewsByHospital(Request $request)
    {
        $hospital = Hospital::find($request->hospital_id);
        if ($hospital) {
            $countReviews = HospitalReview::where('hospital_id', $hospital->id)->count();

            return response()->json([
                'status' => 'success',
                'countReviews' => $countReviews
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "There is no data for provided id#{$request->hospital_id}"
            ], 404);
        }
    }

    /**
     * Hospital review destroyer
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $review = HospitalReview::find($id);

        if ($review) {
            $review->delete();

            return response()->json([
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot find review for id#$id or invalid data"
            ], 404);
        }
    }
}
