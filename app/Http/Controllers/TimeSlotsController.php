<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\TimeSlots;
use Illuminate\Http\Request;
use App\Models\Doctor\Doctor;
use App\Http\Resources\TimeSlotsResource;

class TimeSlotsController extends Controller
{
    /**
     * Display a listing of the TimeSlots.
     */
    public function index()
    {
        $timeslots = TimeSlots::orderBy('start_time')->get();
        return TimeSlotsResource::collection($timeslots);
    }

    /**
     * Display the specified TimeSlot.
     */
    public function show($id)
    {
        $timeslot = TimeSlots::find($id);
        if (!$timeslot) {
            return response()->json([
                'status' => 'failure',
                'message' => "An error occurred while trying to find timeslot for id$id",
            ], 404);
        }
        return new TimeSlotsResource($timeslot);
    }

    /**
     * Display timeslots by Doctor
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showByDoctor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctor = Doctor::find($request->doctor_id);
        if (!$doctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "An error occurred while searching for doctor ID#{$request->doctor_id}"
            ]);
        }

        $doctorTimeslots = TimeSlots::with('doctor.user')->where('doctor_id', $doctor->id)->get();
        if (empty($doctorTimeslots)) {
            return response()->json([
                'data' => []
            ]);
        }
        return TimeSlotsResource::collection($doctorTimeslots);

    }

    /**
     * Display timeslots by serviceId
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showByService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'exists:services,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ]);
        }

        $serviceTimeSlots = TimeSlots::with('service')->where('service_id', $request->service_id)->get();
        if (empty($serviceTimeSlots)) {
            return response()->json([
                'data' => []
            ]);
        }
        return TimeSlotsResource::collection($serviceTimeSlots);
    }

    /**
     * Display timeslots by date
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function showByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Invalid date format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->input('date');
        $timeslots = TimeSlots::byDate($date)->get();

        if ($timeslots->isEmpty()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'No timeslots found for the selected date',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => TimeSlotsResource::collection($timeslots),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'service_id' => ['required', 'exists:services,id'],
            'start_time' => ['required', 'date_format:Y-m-d H:i'],
            'end_time' => ['required', 'date_format:Y-m-d H:i'],
            'price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There are issues with provided data',
                'errors' => $validator->errors(),
            ], 500);
        }

        $timeslot = TimeSlots::create([
            'doctor_id' => $request->doctor_id,
            'service_id' => $request->service_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'price' => $request->price
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'TimeSlot has created successfully',
            'timeslot' => $timeslot
        ]);
    }

    /**
     * Generate TimeSlots
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function generateTimeSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'exists:services,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'start_time' => ['required', 'date_format:Y-m-d H:i', 'before:end_time'],
            'end_time' => ['required', 'date_format:Y-m-d H:i', 'after:start_time'],
            'price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred during validation of provided data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $serviceId = $request->input('service_id');
        $doctorId = $request->input('doctor_id');
        $price = $request->input('price');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        $timeslots = [];

        for ($current = $startTime; $current->lt($endTime); $current->addHour()) {
            $timeslots[] = [
                'service_id' => $serviceId,
                'doctor_id' => $doctorId,
                'start_time' => $current->toDateTimeString(),
                'end_time' => $current->copy()->addHour()->toDateTimeString(),
                'price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TimeSlots::insert($timeslots);

        return response()->json([
            'status' => 'success',
            'message' => "Timeslots successfully generated for serviceId$serviceId",
            'data' => $timeslots
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update($id, Request $request)
    {
        $timeSlot = TimeSlots::find($id);
        if (!$timeSlot) {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred while trying to find provided time_slot id',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'start_time' => ['date_format:Y-m-d H:i'],
            'end_time' => ['date_format:Y-m-d H:i'],
            'price' => ['numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred in provided data',
                'errors' => $validator->errors(),
            ]);
        }

        $timeSlot->update([
            'start_time' => $request->start_time ?? $timeSlot->start_time,
            'end_time' => $request->end_time ?? $timeSlot->end_time,
            'price' => $request->price ?? $timeSlot->price,
        ]);

        return new TimeSlotsResource($timeSlot);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $timeSlot = TimeSlots::find($id);
        if (!$timeSlot) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided timeslot',
            ], 404);
        }
        $timeSlot->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'TimeSlot deleted successfully',
        ]);
    }
}
