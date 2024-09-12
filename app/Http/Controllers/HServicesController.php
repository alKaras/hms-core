<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServicesResource;
use App\Imports\ServicesImport;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HServices;
use Illuminate\Http\Request;
use App\Http\Resources\HospitalResource;
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
        return HospitalResource::collection($services);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['string', 'required', 'max:255'],
            'description' => ['string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'hospital_id' => ['required', 'exists:hospital,id'],
            'doctor_id' => ['required|exists:doctors,id']
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = HServices::create([
            'name' => $request->name,
            'description' => $request->description,
            'department_id' => $request->department_id
        ]);

        $hospital = Hospital::find($request->hospital_id);
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
                'service' => new ServicesResource($service)
            ], 201);
        } else {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred registering service',
            ]);
        }
    }

    /**
     * Import services from XLSX file
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
        $path = $file->store('imports');

        Excel::import(new ServicesImport, storage_path('app/' . $path));

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
    public function destroy(HServices $hServices)
    {
        //
    }
}
