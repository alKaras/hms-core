<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use DB;
use Illuminate\Http\Request;
use App\Http\Resources\HospitalResource;
use Validator;

class HospitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hospitals = Hospital::with("content")->get();
        return HospitalResource::collection($hospitals);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255|required',
            'description' => 'string|max:255',
            'alias' => 'required|max:255|string',
            'address' => 'required|max:255|string',
            'hospital_email' => 'email|unique:hospital,hospital_email',
            'hospital_phone' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $hospital = Hospital::create([
            'alias' => $request->alias,
            'hospital_email' => $request->hospital_email,
            'hospital_phone' => $request->hospital_phone
        ]);

        if ($hospital) {
            DB::table('hospital_content')->insert([
                'hospital_id' => $hospital->id,
                'title' => $request->title,
                'description' => $request->description,
                'address' => $request->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $hospital->load('content');

            return new HospitalResource($hospital);
        } else {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred while creating hospital',
            ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $hospital = Hospital::with('content')->find($id);

        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for given hospital'
            ], 404);
        }
        $hospital->load('content');
        return new HospitalResource($hospital);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $hospital_id)
    {
        $hospital = Hospital::with('content')->find($hospital_id);

        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided hospital'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255|required',
            'description' => 'string|max:255',
            'address' => 'required|max:255|string',
            'hospital_email' => 'unique|email',
            'hospital_phone' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $hospital->update([
            'hospital_email' => $request->hospital_email ?? $hospital->hospital_email,
            'hospital_phone' => $request->hospital_phone ?? $hospital->hospital_phone,
        ]);

        $hospital->content()->update([
            'title' => $request->title,
            'description' => $request->description ?? $hospital->content->description,
            'address' => $request->address
        ]);

        $hospital->load('content');

        return new HospitalResource($hospital);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($hospital_id)
    {
        $hospital = Hospital::with('content')->find($hospital_id);
        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided hospital'
            ], 404);
        }
        $hospital->content()->delete();
        $hospital->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Hospital deleted successfully',
        ], 200);
    }
}
