<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServicesResource;
use App\Imports\ServicesImport;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\HServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class HServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = HServices::all();
        return ServicesResource::collection($services);
    }

    /**
     * Display one service
     */
    public function show($id)
    {
        $services = HServices::find($id);
        if (!$services) {
            return response()->json([
                'status' => 'failure',
                'message' => "No provided services for given id #$id"
            ]);
        }
        return new ServicesResource($services);
    }

    /**
     * Get services by doctorId
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getServicesByDoctorId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'exists:doctors,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failure',
                'message' => 'check provided doctor_id',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = Doctor::with(['services'])->where('id', $request->doctor_id)->first();

        return response()->json([
            'status' => 'success',
            'data' => $doctor->services->map(function ($service) {
                return [
                    "id" => $service->id,
                    "name" => $service->name,
                    "description" => $service->description,
                    "department" => [
                        "id" => $service->department->id,
                        "title" => $service->department->content->title,
                    ]
                ];
            })
        ]);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['string', 'required', 'max:255'],
            'description' => ['max:255'],
            'department' => ['required', 'exists:department,alias'],
            'hospital_id' => ['required', 'exists:hospital,id'],
            'doctor_id' => ['required', 'exists:doctors,id']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $hospital = Hospital::find($request->hospital_id);
        $department = $hospital->departments->where('alias', $request->department)->first();

        if (!$department) {
            return response()->json([
                'status' => 'failure',
                'message' => "Department for provided alias {$request->department} doesn't exist"
            ], 404);
        }

        $service = HServices::create([
            'name' => $request->name,
            'description' => $request->description,
            'department_id' => $department->id
        ]);


        $doctor = Doctor::find($request->doctor_id);

        if ($hospital && $doctor) {
            $service->hospitals()->attach($hospital, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $service->doctors()->attach($doctor, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            return response()->json([
                'status' => 'success',
                'message' => 'Service created successfully',
                'service' => $service
            ], 201);
        } else {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred registering service',
            ], 500);
        }
    }

    /**
     * Import services using xlsx file handler
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'mimes:xlsx'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $file->storeAs('imports', $originalFileName);

        try {
            Excel::import(new ServicesImport, $file);
        } catch (\Exception $e) {
            \Log::error('Import failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import services.',
                'error' => $e->getMessage()
            ], 500);
        }



        return response()->json([
            'status' => 'success',
            'message' => 'Services imported successfully'
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HServices $hServices)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $service = HServices::find($id);
        if (!$service) {
            return response()->json([
                'status' => 'failure',
                'message' => "Can\'t find any services by provided id #$id",
            ], 404);
        }

        $service->doctors()->detach();
        $service->hospitals()->detach();
        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Service and connected entities deleted successfully',
        ]);
    }
}
