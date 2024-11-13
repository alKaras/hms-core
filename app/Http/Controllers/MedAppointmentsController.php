<?php

namespace App\Http\Controllers;

use App\Models\TimeSlots;
use Illuminate\Http\Request;
use App\Models\MedAppointments;
use App\Enums\TimeslotStateEnum;
use App\Enums\AppointmentsStatusEnum;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MedAppointmentResource;

class MedAppointmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medappointments = MedAppointments::all();

        return new MedAppointmentResource($medappointments);

    }

    public function show(Request $request)
    {
        $appointment = MedAppointments::find($request->input('appointmentId'));

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no appointment for provided id'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data' => MedAppointmentResource::collection($appointment),
        ]);
    }

    public function getByDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $medappointments = MedAppointments::where('doctor_id', $request->doctor_id)->with('timeslot')
            ->orderBy(TimeSlots::select('start_time')->whereColumn('id', 'med_appointments.time_slot_id'))->paginate($perPage);

        return response()->json([
            'status' => 'ok',
            'data' => MedAppointmentResource::collection($medappointments),
            'meta' => [
                'current_page' => $medappointments->currentPage(),
                'per_page' => $medappointments->perPage(),
                'total' => $medappointments->total(),
                'last_page' => $medappointments->lastPage(),
            ],
            'links' => [
                'first' => $medappointments->url(1),
                'last' => $medappointments->url($medappointments->lastPage()),
                'prev' => $medappointments->previousPageUrl(),
                'next' => $medappointments->nextPageUrl(),
            ]
        ]);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'referral_id' => ['exists:user_referrals,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctor = $request->input('doctor_id');
        $timeslot = $request->input('time_slot_id');
        $referral = $request->input('referral_id', null);
        $user = $request->input('user_id');

        $appointment = MedAppointments::create([
            'doctor_id' => $doctor,
            'time_slot_id' => $timeslot,
            'referral_id' => $referral,
            'user_id' => $user,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Appointment created successfully',
            'data' => MedAppointmentResource::collection($appointment),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment' => ['required', 'exists:med_appointments,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'summary' => ['text'],
            'notes' => ['text'],
            'recommendations' => ['text']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appointment = MedAppointments::find($request->input('appointment'));

        try {
            $appointment->update([
                'summary' => $request->input('summary') ?? $appointment->summary,
                'notes' => $request->input('notes') ?? $appointment->notes,
                'recommendations' => $request->input('recommendations') ?? $appointment->recommendations,
                'updated_at' => now(),
            ]);

            return new MedAppointmentResource($appointment);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $appointment = MedAppointments::find($request->input('appointmentId'));

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no appointment for provided id'
            ], 404);
        }

        $appointment->update([
            'updated_at' => now(),
            'status' => AppointmentsStatusEnum::CANCELLED,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Appointment cancelled successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $appointment = MedAppointments::find($request->input('appointmentId'));

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no appointment for provided id'
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Appointment deleted successfully',
        ]);
    }
}
