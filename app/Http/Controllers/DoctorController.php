<?php

namespace App\Http\Controllers;

use App\Imports\DoctorImport;
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
     * Add doctor method
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialization' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users'],
            'phone' => ['required', 'numeric'],
            'departments' => ['required', 'array'],
            'departments.*' => ['required', 'string', 'max:255'],
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

            $neccessaryRoles = Role::whereIn('title', ['user', 'doctor'])->get();

            if ($neccessaryRoles->isEmpty()) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'No valid rows for provided roles',
                ]);
            }

            if ($user) {
                $now = now();
                $syncData = $neccessaryRoles->pluck('id')->mapWithKeys(function ($roleId) use ($now) {
                    return [$roleId => ['created_at' => $now, 'updated_at' => $now]];
                });
                $user->roles()->sync($syncData);
            }
        }

        $doctor = Doctor::create([
            'specialization' => $request->specialization,
            'user_id' => $user->id,
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
                'message' => 'Failed to import services.',
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
            'specialization' => $request->specialization,
        ]);

        $departments = Department::whereIn('title', $request->departments)->get();

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
