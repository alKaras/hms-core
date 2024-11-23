<?php

namespace App\Http\Controllers;

use App\Models\Doctor\DoctorServices;
use App\Models\HServices;
use Illuminate\Http\Request;
use App\Models\Doctor\Doctor;
use App\Imports\ServicesImport;
use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\ServicesResource;
use Illuminate\Support\Facades\Validator;

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
                'status' => 'error',
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
                'status' => 'error',
                'message' => 'check provided doctor_id',
                'errors' => $validator->errors()
            ], 422);
        }

        $doctor = Doctor::with(['services'])->where('id', $request->doctor_id)->first();

        return response()->json([
            'status' => 'ok',
            'data' => $doctor->services->map(function ($service) {
                return [
                    "id" => $service->id,
                    "service_name" => $service->name,
                    "description" => $service->description,
                    "department" => $service->department->content->title
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
                'status' => 'error',
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
                'status' => 'ok',
                'message' => 'Service created successfully',
                'service' => $service
            ], 201);
        } else {
            return response()->json([
                'status' => 'error',
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
            'status' => 'ok',
            'message' => 'Services imported successfully'
        ]);

    }

    /**
     * Attach existed by service department doctors to the service
     */
    public function attachDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'exists:services,id'],
            'doctors' => ['required', 'array'],
            'doctors.*' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data is incorrect',
                'error' => $validator->errors()
            ], 422);
        }

        $serviceId = $request->input('service_id');
        $doctorIds = $request->input('doctors');

        foreach ($doctorIds as $doctor) {
            DB::table("doctor_services")->insert([
                'service_id' => $serviceId,
                'doctor_id' => $doctor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DoctorServices::where('service_id', '=', $serviceId)->count() > 0) {
            return response()->json([
                'status' => 'ok',
                'message' => "Doctors attached to the service {$serviceId} successfully"
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Detached existed service doctors
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function detachDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'exists:services,id'],
            'doctors' => ['required', 'array'],
            'doctors.*' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data is incorrect',
                'error' => $validator->errors()
            ], 422);
        }

        $serviceId = $request->input('service_id');
        $doctorIds = $request->input('doctors');

        $deleted = DB::table('doctor_services')
            ->whereIn('doctor_id', $doctorIds)
            ->where('service_id', $serviceId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status' => 'ok',
                'message' => "Doctors detached from the service {$serviceId} successfully",
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $service = HServices::find($id);
        if (!$service) {
            return response()->json([
                'status' => 'error',
                'message' => "Can\'t find any services by provided id #$id",
            ], 404);
        }

        $service->doctors()->detach();
        $service->hospitals()->detach();
        $service->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Service and connected entities deleted successfully',
        ]);
    }
}
