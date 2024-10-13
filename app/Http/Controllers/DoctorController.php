<?php

namespace App\Http\Controllers;

use App\Imports\DoctorImport;
use App\Models\HServices;
use App\Notifications\DoctorCredentialsNotification;
use Validator;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Doctor\Doctor;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Department\Department;
use App\Http\Resources\DoctorResource;
use Illuminate\Validation\Rules\Password;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::all();
        return DoctorResource::collection($doctors);
    }


    /**
     * Show doctors by service Id
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function showByServiceId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'exists:services,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = HServices::find($request->input('service_id'));

        $doctors = $service->doctors;
        return response()->json([
            'status' => 'success',
            'data' => $doctors->filter(function ($doctor) {
                return $doctor->hidden === 0;
            })
                ->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->user->name,
                        'surname' => $doctor->user->surname,
                        'email' => $doctor->user->email,
                    ];
                })
        ]);
    }

    /**
     * Add doctor method
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialization' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'phone' => ['required', 'numeric'],
            'departments' => ['required', 'array'],
            'departments.*' => ['required', 'string', 'max:255'],
            'hospital_id' => ['required', 'exists:hospital,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $password = Str::password($length = 12, $letters = true, $numbers = true, $symbols = true);
            $user = User::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($password),
            ]);

            $userRole = Role::where('title', 'user')->value('id');
            $doctorRole = Role::where('title', 'doctor')->value('id');

            if ($user) {
                $user->roles()->attach($userRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $user->roles()->attach($doctorRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $user->notify(new DoctorCredentialsNotification($user->email, $password));
        }

        $existedDoctor = Doctor::where('user_id', $user->id)->first();
        if ($existedDoctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "The doctor for this user id {$user->id} has already been created"
            ], 500);
        }

        $doctor = Doctor::create([
            'specialization' => $request->specialization,
            'user_id' => $user->id,
            'hospital_id' => $request->hospital_id,
        ]);

        $departments = Department::whereHas('content', function ($query) use ($request) {
            $query->whereIn('title', $request->departments);
        })->get();

        if ($departments->isEmpty()) {
            return response()->json([
                'error' => 'No valid departments found'
            ], 404);
        }

        $doctor->departments()->attach($departments, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        return response()->json([
            'status' => 'success',
            'message' => 'Doctor created successfully',
            'data' => new DoctorResource($doctor)
        ]);
    }


    /**
     * Bulk import doctors list
     */
    public function importDoctors(Request $request)
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
            Excel::import(new DoctorImport, $file);
        } catch (\Exception $e) {
            \Log::error('Import failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import doctors.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Doctors imported successfully'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "Cannot find any doctor for provided id#$id",
            ]);
        }
        return new DoctorResource($doctor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "Cannot find any doctors for provided id #$id"
            ]);
        }

        $validator = Validator::make($request->all(), [
            'specialization' => ['required', 'string'],
            'departments' => ['required', 'array'],
            'departments.*' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ]);
        }

        $doctor->update([
            'specialization' => $request->specialization ?? $doctor->specialization,
        ]);

        $departments = Department::whereHas('content', function ($query) use ($request) {
            $query->whereIn('title', $request->departments);
        })->get();

        if ($departments->isEmpty()) {
            return response()->json([
                'error' => 'No valid departments found'
            ], 404);
        }

        $doctor->departments()->detach();
        $doctor->departments()->attach($departments, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new DoctorResource($doctor);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json([
                'status' => 'failure',
                'message' => "Cannot find any doctor for provided id#$id",
            ]);
        }

        $doctor->departments()->detach();
        $doctor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Doctor deleted successfully',
        ]);
    }
}
