<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedCardResource;
use App\Models\MedCard;
use Illuminate\Http\Request;
use Validator;

class MedCardController extends Controller
{
    public function index()
    {
        $medcards = MedCard::all();

        return MedCardResource::collection($medcards);
    }

    public function showById($id)
    {
        $medcard = MedCard::find($id);

        if (!$medcard) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided id'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data' => new MedCardResource($medcard)
        ]);
    }

    public function showByUser(Request $request)
    {
        $medcard = MedCard::where('user_id', $request->user_id)->first();
        $completedOnly = true;

        if (!$medcard) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided id'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data' => new MedCardResource($medcard, $completedOnly)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'firstname' => ['required', 'string'],
            'lastname' => ['required', 'string'],
            'date_birthday' => ['date', 'nullable'],
            'gender' => ['required', 'in:male,female,non-binary'],
            'contact_number' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'blood_type' => ['nullable', 'string', 'max:3'],
            'allergies' => ['nullable', 'string', 'max:5000'],
            'chronic_conditions' => ['nullable', 'string', 'max:5000'],
            'current_meddications' => ['nullable', 'string', 'max:5000'],
            'emergency_contact_name' => ['string', 'required'],
            'emergency_contact_phone' => ['required', 'string'],
            'insurance_details' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:active,inactive,archived'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors()
            ], 422);
        }

        $existedMedCard = MedCard::where('user_id', $request->user_id)->first();

        if ($existedMedCard) {
            return response()->json([
                'status' => 'error',
                'message' => "Med card for provided user is already existed"
            ], 500);
        }

        $medcard = MedCard::create([
            'user_id' => $request->user_id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'date_birthday' => $request->date_birthday ?? null,
            'gender' => $request->gender,
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'blood_type' => $request->blood_type ?? null,
            'allergies' => $request->allergies ?? null,
            'chronic_conditions' => $request->chronic_conditions ?? null,
            'current_medications' => $request->current_medications ?? null,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'insurance_details' => $request->insurance_details ?? null,
            'additional_notes' => $request->additional_notes ?? null,
            'status' => $request->status ?? 'active'
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => "Med card for user#{$request->user_id} created successfully",
            'data' => new MedCardResource($medcard)
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medcard_id' => ['required', 'exists:medcards,id'],
            'firstname' => ['string'],
            'lastname' => ['string'],
            'gender' => ['in:male,female,non-binary'],
            'contact_number' => ['string', 'max:20'],
            'address' => ['string'],
            'allergies' => ['nullable', 'string', 'max:5000'],
            'chronic_conditions' => ['nullable', 'string', 'max:5000'],
            'current_meddications' => ['nullable', 'string', 'max:5000'],
            'emergency_contact_name' => ['string'],
            'emergency_contact_phone' => ['string'],
            'insurance_details' => ['nullable', 'string', 'max:5000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:active,inactive,archived'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors()
            ], 422);
        }

        $medcard = MedCard::find($request->medcard_id);

        try {
            $medcard->update([
                'firstname' => $request->firstname ?? $medcard->firstname,
                'lastname' => $request->lastname ?? $medcard->lastname,
                'gender' => $request->gender ?? $medcard->gender,
                'contact_number' => $request->contact_number ?? $medcard->contact_number,
                'address' => $request->address ?? $medcard->address,
                'allergies' => $request->allergies ?? $medcard->allergies,
                'chronic_conditions' => $request->chronic_conditions ?? $medcard->chronic_conditions,
                'current_medications' => $request->current_medications ?? $medcard->current_medications,
                'emergency_contact_name' => $request->emergency_contact_name ?? $medcard->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone ?? $medcard->emergency_contact_phone,
                'insurance_details' => $request->insurance_details ?? $medcard->insurance_details,
                'additional_notes' => $request->additional_notes ?? $medcard->additional_notes,
                'status' => $request->status ?? $medcard->status,
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Medcard has been updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $medcard = MedCard::find($request->input('medcard_id'));

            if (!$medcard) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no data for provided id'
                ], 404);
            }

            $medcard->delete();

            return response()->json([
                'status' => 'ok',
                'message' => 'MedCard deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
