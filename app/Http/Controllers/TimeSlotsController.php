<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\HServices;
use App\Models\TimeSlots;
use Illuminate\Http\Request;
use App\Models\Doctor\Doctor;
use App\Enums\TimeslotStateEnum;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;
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
            'freeOnly' => ['numeric']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ], 422);
        }
        $freeOnly = $request->input('freeOnly');

        $doctor = Doctor::find($request->doctor_id);
        if (!$doctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "An error occurred while searching for doctor ID#{$request->doctor_id}"
            ]);
        }

        $doctorTimeslots = TimeSlots::with('doctor.user')->where('doctor_id', $doctor->id)->get();
        if ($freeOnly) {
            $timeslotsFiltered = $doctorTimeslots->filter(function ($timeSlots) {
                return $timeSlots->state == TimeslotStateEnum::FREE;
            });


            if (!empty($timeslotsFiltered)) {
                return TimeSlotsResource::collection($timeslotsFiltered);
            }
            return response()->json([
                'data' => []
            ], 404);
        }

        if (empty($doctorTimeslots)) {
            return response()->json([
                'data' => []
            ], 404);
        }

        return TimeSlotsResource::collection($doctorTimeslots);

    }

    /**
     * Show free slots by service|doctor
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function showFreeSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['exists:service,id'],
            'doctor_id' => ['exists:doctors,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not validated data',
                'errors' => $validator->errors()
            ]);
        }

        $serviceId = $request->input('service_id');
        $doctorId = $request->input('doctor_id');

        $freeSlotsCounter = DB::table('time_slots')
            ->when($serviceId, function ($query) use ($serviceId) {
                return $query->whereNotNull('service_id');
            })
            ->when($doctorId, function ($query) use ($doctorId) {
                return $query->whereNotNull('doctor_id');
            })
            ->groupBy('service_id', 'DATE(start_time)')
            ->selectRaw("
            service_id as `serviceId`,
            DATE(start_time) as `date`,
            COUNT(*) as `free_slots`
        ")->get();

        return response()->json([
            'status' => 'success',
            'data' => $freeSlotsCounter,
        ]);
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
            'freeOnly' => 'numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Check provided data',
                'errors' => $validator->errors(),
            ]);
        }

        $freeOnly = $request->input('freeOnly');
        $serviceTimeSlots = TimeSlots::with('service')->where('service_id', $request->service_id)->get();

        if ($freeOnly) {
            $serviceSlotsFiltered = $serviceTimeSlots->filter(function ($timeSlots) {
                return $timeSlots->state == TimeslotStateEnum::FREE;
            });

            if (!empty($serviceSlotsFiltered)) {
                return TimeSlotsResource::collection($serviceSlotsFiltered);
            }
            return response()->json(['data' => [], 404]);
        }

        if (empty($serviceTimeSlots)) {
            return response()->json([
                'data' => []
            ], 404);
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
            'date' => ['required', 'date_format:Y-m-d'],
            'service_id' => ['exists:services,id'],
            'doctor_id' => ['exists:doctors,id'],
            'freeOnly' => ['numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Invalid data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->input('date');
        $service = HServices::find($request->input('service_id'));
        $doctor = Doctor::find($request->input('doctor_id'));
        $timeslots = TimeSlots::byDate($date)->get();
        $freeOnly = $request->input('freeOnly');

        if ($timeslots->isEmpty()) {
            return response()->json([
                'status' => 'failure',
                'data' => [],
            ], 200);
        }

        if (null !== $service) {
            $serviceTimeSlots = $timeslots->filter(fn($timeslot) => $timeslot->service_id == $service->id);

            if ($freeOnly) {
                $filteredServicesSlots = $serviceTimeSlots->filter(fn($timeslot) => $timeslot->state === TimeslotStateEnum::FREE);

                if (!empty($filteredServicesSlots)) {
                    return response()->json([
                        'status' => 'success',
                        'data' => TimeSlotsResource::collection($filteredServicesSlots)
                    ]);
                } else {
                    return response()->json([
                        'status' => 'failure',
                        'data' => []
                    ], 404);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => TimeSlotsResource::collection($serviceTimeSlots),
            ]);
        } elseif (null !== $doctor) {
            $doctorTimeSlots = $timeslots->filter(fn($timeslot) => $doctor->id == $timeslot->doctor_id);

            if ($freeOnly) {
                $filteredDoctorSlots = $doctorTimeSlots->filter(fn($timeslot) => $timeslot->state === TimeslotStateEnum::FREE);

                if (!empty($filteredDoctorSlots)) {
                    return response()->json([
                        'status' => 'success',
                        'data' => TimeSlotsResource::collection($filteredDoctorSlots),
                    ]);
                } else {
                    return response()->json([
                        'status' => 'failure',
                        'data' => [],
                    ], 404);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => TimeSlotsResource::collection($doctorTimeSlots),
            ]);
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
            'doctor_id' => ['exists:doctors,id'],
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
            'doctor_id' => $request->doctor_id ?? $timeSlot->doctor_id,
            'start_time' => $request->start_time ?? $timeSlot->start_time,
            'end_time' => $request->end_time ?? $timeSlot->end_time,
            'price' => $request->price ?? $timeSlot->price,
        ]);

        return new TimeSlotsResource($timeSlot);
    }

    /**
     * Generate Pdf of timeslot
     * @param mixed $id
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $timeSlot = TimeSlots::find($id);
        if ($timeSlot) {
            // $details = ['title' => 'test'];
            $details = (new TimeSlotsResource($timeSlot))->toArray(request());
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.specific_timeslot', compact('details'));
            return $pdf->download("timeslot-{$timeSlot->id}-{$timeSlot->service_id}-{$timeSlot->doctor_id}.pdf");
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "There is no data for provided id#$id",
            ], 404);
        }
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
