<?php

namespace App\Http\Controllers;

use App\Models\Hospital\HospitalDepartments;
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

    public function getUnassignedDepartments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'exists:hospital,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ]);
        }

        $hospitalId = $request->input('hospital_id');

        $assignedDepartments = HospitalDepartments::where('hospital_id', $hospitalId)->pluck('department_id')->toArray();
        $unassignedDepartments = Department::whereNotIn('id', $assignedDepartments)->get();

        $unassignedDepartments->load('content');
        return response()->json([
            'status' => 'ok',
            'data' => DepartmentResource::collection($unassignedDepartments),
        ]);
    }

    /**
     * Attach existed departments to the hospital
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function attachExistedDepartments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'exists:hospital,id'],
            'department_ids.*' => ['required', 'exists:department,id'],
            'department_ids' => ['required', 'array']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $departmentIds = $request->input('department_ids');

        // $departments = Department::whereIn('id', $request->department_id)->get();

        foreach ($departmentIds as $department) {
            DB::table('hospital_departments')->insert([
                'hospital_id' => $request->hospital_id,
                'department_id' => $department,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        if (HospitalDepartments::where('hospital_id', '=', $request->hospital_id)->count() > 0) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Departments attached to the hospital successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong'
            ], 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            ], 422);
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
                    'status' => 'error',
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
                'status' => 'error',
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
            'status' => 'ok',
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
                'status' => 'error',
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
                'status' => 'error',
                'message' => 'There is no data for given department'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['string', 'max:255'],
            'description' => ['string', 'max:255'],
            'email' => ['email'],
            'phone' => ['string', 'max:13'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }

        $department->update([
            'email' => $request->email ?? $department->email,
            'phone' => $request->phone ?? $department->phone,
        ]);

        $department->content()->update([
            'title' => $request->title ?? $department->title,
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
                'status' => 'error',
                'message' => 'There is no data for given department'
            ], 404);
        }

        $department->content()->delete();
        $department->hospitals()->detach();
        $department->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Department and related tables deleted successfully'
        ]);
    }
}
