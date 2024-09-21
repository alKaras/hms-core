<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Hospital\Hospital;
use App\Http\Resources\HospitalResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DepartmentResource;

class HospitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hospitals = Hospital::with("content")->get();
        return HospitalResource::collection($hospitals);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255|required',
            'description' => 'string|max:255',
            'alias' => 'required|max:255|string',
            'address' => 'required|max:255|string',
            'hospital_email' => 'email|unique:hospital,hospital_email',
            'hospital_phone' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $hospital = Hospital::create([
            'alias' => $request->alias,
            'hospital_email' => $request->hospital_email,
            'hospital_phone' => $request->hospital_phone
        ]);

        if ($hospital) {
            DB::table('hospital_content')->insert([
                'hospital_id' => $hospital->id,
                'title' => $request->title,
                'description' => $request->description ?? "",
                'address' => $request->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $hospital->load('content');

            return new HospitalResource($hospital);
        } else {
            return response()->json([
                'status' => 'failure',
                'message' => 'An error occurred while creating hospital',
            ]);
        }

    }

    /**
     * Display the specified hospital.
     */
    public function show($id)
    {
        $hospital = Hospital::with('content')->find($id);

        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for given hospital'
            ], 404);
        }
        $hospital->load('content');
        return new HospitalResource($hospital);
    }


    /**
     * Show hospital departments
     * @param mixed $hospital_id
     * @return mixed|\Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function showHospitalDepartments($hospital_id)
    {
        $hospital = Hospital::with(['departments.content'])
            ->where('id', $hospital_id)
            ->first();
        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => "No data for provided id {$hospital_id}",
            ]);
        }

        return DepartmentResource::collection($hospital->departments);
    }

    /**
     * Show all doctors in the department
     */
    public function fetchDepartmentDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'integer', 'exists:hospital,id'],
            'dep_alias' => ['string', 'exists:department,alias']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided ids',
                'error' => $validator->errors()
            ], 422);
        }
        $hospital = Hospital::with(['departments.doctors.user'])
            ->where('id', $request->hospital_id)
            ->first();

        if (!$hospital) {
            return response()->json(['message' => 'Hospital not found'], 404);
        }

        if ($request->dep_alias) {
            $department = $hospital->departments->where('alias', $request->dep_alias)->first();

            if (!$department) {
                return response()->json(['message' => 'Department not found in this hospital'], 404);
            }
            $doctors = $department->doctors;

            return response()->json([
                'status' => 'success',
                'doctors' => $doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->user->name,
                        'surname' => $doctor->user->surname,
                        'email' => $doctor->user->email,
                        'specialization' => $doctor->specialization,
                        'hidden' => $doctor->hidden,
                    ];
                })
            ]);
        }

        $doctors = DB::table('hospital_departments as hd')
            ->join('department as d', 'd.id', '=', 'hd.department_id')
            ->join('department_content as dc', 'dc.department_id', '=', 'd.id')
            ->join('doctor_departments as dd', 'dd.department_id', '=', 'd.id')
            ->join('doctors as doctor', 'doctor.id', '=', 'dd.doctor_id')
            ->join('users as u', 'u.id', '=', 'doctor.user_id')
            ->where('hd.hospital_id', $hospital->id)
            ->groupBy('doctor.id')
            ->selectRaw('doctor.id as id, u.name as name, u.surname as surname, u.email as email, doctor.specialization as specialization, doctor.hidden, GROUP_CONCAT(" ", dc.title) as department_titles')
            ->get();

        return response()->json([
            'status' => 'success',
            'doctors' => collect($doctors)->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'surname' => $doctor->surname,
                    'email' => $doctor->email,
                    'specialization' => $doctor->specialization,
                    'hidden' => $doctor->hidden,
                    'department_titles' => array_map('trim', explode(',', $doctor->department_titles))
                ];
            })
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $hospital_id)
    {
        $hospital = Hospital::with('content')->find($hospital_id);

        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided hospital'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'string|max:255',
            'address' => 'max:255|string',
            'hospital_email' => 'unique:hospital,hospital_email|string',
            'hospital_phone' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], 422);
        }

        $hospital->update([
            'hospital_email' => $request->hospital_email ?? $hospital->hospital_email,
            'hospital_phone' => $request->hospital_phone ?? $hospital->hospital_phone,
        ]);

        $hospital->content()->update([
            'title' => $request->title ?? $hospital->content->title,
            'description' => $request->description ?? $hospital->content->description,
            'address' => $request->address ?? $hospital->content->address
        ]);

        $hospital->load('content');

        return new HospitalResource($hospital);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($hospital_id)
    {
        $hospital = Hospital::with('content')->find($hospital_id);
        if (!$hospital) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no data for provided hospital'
            ], 404);
        }
        $hospital->content()->delete();
        $hospital->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Hospital deleted successfully',
        ], 200);
    }
}
