<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Imports\DepartmentImport;
use App\Models\Hospital\Hospital;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Department\Department;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DepartmentResource;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $department = Department::with('content')->get();
        return DepartmentResource::collection($department);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->al(), [
            'alias' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:13'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'hospital_id' => ['required', 'exists:hospital,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ]);
        }

        $department = Department::create([
            'alias' => $request->alias,
            'email' => $request->email,
            'phone' => $request->phone
        ]);

        if ($department) {
            DB::table('department_content')->insert([
                'department_id' => $department->id,
                'title' => $request->title,
                'description' => $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $hospital = Hospital::find($request->hospital_id);

            if (!$hospital) {
                return response()->json([
                    'status' => 'failure',
                    'message' => "No hospitals for provided id{$request->hospital_id}"
                ], 404);
            }

            $department->hospitals()->attach($hospital, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $department->load('content');
            return new DepartmentResource($department);

        } else {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred while creating department',
            ], 500);
        }


    }

    /**
     * Import departments from xlsx file
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
        $originalName = $file->getClientOriginalName();
        $file->storeAs('imports', $originalName);

        try {
            Excel::import(new DepartmentImport, $file);
        } catch (Exception $e) {
            \Log::error('Import failed', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import departments',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Departments imported successfully'
        ]);
    }

    /**
     * Display the specified department.
     */
    public function show($id)
    {
        $department = Department::with('content')->find($id);

        if (!$department) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for given department'
            ], 404);
        }

        $department->load('content');
        return new DepartmentResource($department);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $department = Department::with('content')->find($id);

        if (!$department) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for given department'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['string', 'max:13'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ]);
        }

        $department->update([
            'email' => $request->email ?? $department->email,
            'phone' => $request->phone ?? $department->phone,
        ]);

        $department->content()->update([
            'title' => $request->title,
            'description' => $request->description ?? $department->description,
        ]);

        $department->load('content');
        return new DepartmentResource($department);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $department = Department::with('content')->find($id);
        if (!$department) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for given department'
            ], 404);
        }

        $department->content()->delete();
        $department->hospitals()->detach();
        $department->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Department and related tables deleted successfully'
        ]);
    }
}
