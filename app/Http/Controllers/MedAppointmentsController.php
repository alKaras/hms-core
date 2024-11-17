<?php

namespace App\Http\Controllers;

use App\Customs\Services\MeetHandlerService;
use App\Models\MedCard;
use App\Models\TimeSlots;
use App\Models\User\User;
use App\Notifications\AppointmentSummaryNotification;
use Illuminate\Http\Request;
use App\Models\MedAppointments;
use App\Enums\TimeslotStateEnum;
use App\Enums\AppointmentsStatusEnum;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MedAppointmentResource;

class MedAppointmentsController extends Controller
{

    public function __construct(public MeetHandlerService $meetHandlerService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medappointments = MedAppointments::all();

        return [
            'status' => 'ok',
            'data' => MedAppointmentResource::collection($medappointments)
        ];

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
            'data' => new MedAppointmentResource($appointment),
        ]);
    }

    public function getUserAppointments(Request $request)
    {
        $appointment = MedAppointments::where('user_id', '=', $request->input('user_id'))->get();

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no appointment for provided userId'
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
        $timeslot = TimeSlots::find($request->input('time_slot_id'));
        $referral = $request->input('referral_id', null);
        $user = $request->input('user_id');
        $medcard = MedCard::where('user_id', $user)->first();

        $appointment = MedAppointments::create([
            'doctor_id' => $doctor,
            'time_slot_id' => $timeslot->id,
            'referral_id' => $referral,
            'user_id' => $user,
            'status' => AppointmentsStatusEnum::SCHEDULED,
            'medcard_id' => $medcard->id ?? null,
            'meet_link' => $timeslot->online ? $this->meetHandlerService->createLink() : null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Appointment created successfully'
            // 'data' => MedAppointmentResource::collection($appointment),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment' => ['required', 'exists:med_appointments,id'],
            // 'doctor_id' => ['required', 'exists:doctors,id'],
            'summary' => ['string', 'max:5000'],
            'notes' => ['string', 'max:5000'],
            'recommendations' => ['string', 'max:5000']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appointment = MedAppointments::find($request->input('appointment'));
        $medcard = MedCard::where('user_id', $appointment->user_id)->first();

        try {
            $appointment->update([
                'summary' => $request->input('summary') ?? $appointment->summary,
                'notes' => $request->input('notes') ?? $appointment->notes,
                'recommendations' => $request->input('recommendations') ?? $appointment->recommendations,
                'medcard_id' => $appointment->medcard_id !== null ? $appointment->medcard_id : $medcard->id,
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

    /**
     * Confirm med appointment. Allow only after doctor consultation
     * @param \Illuminate\Http\Request $request
     * @return MedAppointmentResource|mixed|\Illuminate\Http\JsonResponse
     */
    public function confirmAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment' => ['required', 'exists:med_appointments,id'],
            // 'doctor_id' => ['required', 'exists:doctors,id'],
            'summary' => ['string', 'max:5000'],
            'notes' => ['string', 'max:5000'],
            'recommendations' => ['string', 'max:5000']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appointment = MedAppointments::find($request->input('appointment'));
        $medcard = MedCard::where('user_id', $appointment->user_id)->first();

        try {
            $appointment->update([
                'summary' => $request->input('summary') ?? $appointment->summary,
                'notes' => $request->input('notes') ?? $appointment->notes,
                'recommendations' => $request->input('recommendations') ?? $appointment->recommendations,
                'status' => AppointmentsStatusEnum::COMPLETED,
                'medcard_id' => $appointment->medcard_id !== null ? $appointment->medcard_id : $medcard->id,
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

    /**
     * Generate Pdf file for summary of consultation
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateSummaryPdf($id)
    {
        $appointmentSum = MedAppointments::find($id);

        if ($appointmentSum) {
            $details = (new MedAppointmentResource($appointmentSum))->toArray(request());

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.appointment_summary', compact('details'));

            return $pdf->download("appointment-{$appointmentSum->id}.pdf");
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "There are no appointments for provided id{$id}",
            ], 404);
        }
    }


    /**
     * Send summary notification after confirmation
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendSummaryNotification(Request $request)
    {
        $appointmentSum = MedAppointments::find($request->input('appointmentId'));

        if ($appointmentSum && $appointmentSum->status == AppointmentsStatusEnum::COMPLETED) {
            $user = User::find($appointmentSum->user_id);

            $user->notify(new AppointmentSummaryNotification($appointmentSum));

            return response()->json([
                'status' => 'ok',
                'message' => 'Letter with summary sent successfully',
            ]);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => "There are no appointments for provided id{$request->appointmentId} or status of appointment is not completed"
            ], 404);
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
