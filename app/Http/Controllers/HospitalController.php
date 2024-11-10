<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Doctor\Doctor;
use App\Models\HospitalReview;
use App\Models\Hospital\Hospital;
use App\Http\Resources\HospitalResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DepartmentResource;
use App\Models\Hospital\HospitalDepartments;

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
                'status' => 'error',
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
                'status' => 'error',
                'message' => 'There is no data for given hospital'
            ], 404);
        }
        $hospital->load('content');
        return new HospitalResource($hospital);
    }

    /**
     * Display hospital services
     * @param mixed $hospital_id
     * @return void
     */
    public function showHospitalServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hospital_id' => ['required', 'integer', 'exists:hospital,id']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided ids',
                'error' => $validator->errors()
            ], 422);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $hospitalId = $request->input('hospital_id');

        $servicesData = DB::table('hms.hospital_services as hs')
            ->join('hms.services as s', 'hs.service_id', '=', 's.id')
            ->leftJoin('hms.doctor_services as ds', 's.id', '=', 'ds.service_id')
            ->leftJoin('hms.doctors as d', 'ds.doctor_id', '=', 'd.id')
            ->leftJoin('hms.users as u', 'd.user_id', '=', 'u.id')
            ->leftJoin("department as dep", 'dep.id', '=', 's.department_id')
            ->leftJoin("department_content as dc", 'dc.department_id', '=', 'dep.id')
            ->where('hs.hospital_id', $hospitalId)
            ->where(function ($query) use ($hospitalId) {
                $query->where('u.hospital_id', $hospitalId)
                    ->orWhereNull('u.hospital_id');
            })
            ->selectRaw(
                's.id as service_id, 
            s.name as service_name, 
            IFNULL(s.description, "") as description,
            dc.title as department,
            IF (u.hospital_id is not null,
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    "doctor_id", d.id,
                    "specialization", d.specialization,
                    "name", CONCAT(u.name, " ", u.surname),
                    "email", u.email
                )
            ), JSON_ARRAY()) as doctorsInfo'
            )
            ->groupBy('s.id', 's.name', 'dc.title', 'u.hospital_id')
            ->paginate($perPage, ['*'], 'page', $page);


        $servicesData->getCollection()->transform(function ($service) {
            return [
                'id' => $service->service_id,
                'service_name' => $service->service_name,
                'description' => $service->description,
                'department' => $service->department,
                'doctorInfo' => json_decode($service->doctorsInfo, true)
            ];
        });

        return response()->json([
            'status' => 'ok',
            'services' => $servicesData->items(),
            'meta' => [
                'current_page' => $servicesData->currentPage(),
                'per_page' => $servicesData->perPage(),
                'total' => $servicesData->total(),
                'last_page' => $servicesData->lastPage(),
            ],
            'links' => [
                'first' => $servicesData->url(1),
                'last' => $servicesData->url($servicesData->lastPage()),
                'prev' => $servicesData->previousPageUrl(),
                'next' => $servicesData->nextPageUrl(),
            ]
        ]);
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
                'status' => 'error',
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
        $hospital = Hospital::find($request->hospital_id);

        if ($request->dep_alias) {
            $department = $hospital->departments->where('alias', $request->dep_alias)->first();

            if (!$department) {
                return response()->json(['message' => 'Department not found in this hospital'], 404);
            }
            $doctors = $department->doctors;

            return response()->json([
                'status' => 'ok',
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

        $doctors = DB::table('doctors as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('doctor_departments as dd', 'dd.doctor_id', '=', 'd.id')
            ->leftJoin('department_content as dc', 'dc.department_id', '=', 'dd.department_id')
            ->leftJoin('doctor_services as ds', 'ds.doctor_id', '=', 'd.id')
            ->leftJoin('services as s', 's.id', '=', 'ds.service_id')
            ->where('u.hospital_id', $hospital->id)
            ->groupBy('d.id')
            ->selectRaw('d.id as id, u.name as name, u.surname as surname, u.email as email, d.specialization as specialization, d.hidden, GROUP_CONCAT(distinct dc.title) as departments, GROUP_CONCAT(distinct s.name) as services')
            ->get();

        return response()->json([
            'status' => 'ok',
            'doctors' => collect($doctors)->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'surname' => $doctor->surname,
                    'email' => $doctor->email,
                    'specialization' => $doctor->specialization,
                    'hidden' => $doctor->hidden,
                    'departments' => array_map('trim', explode(',', $doctor->departments)),
                    'services' => $doctor->services ? array_map('trim', explode(',', $doctor->services)) : [],
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
                'status' => 'error',
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
     * Average rating for specific hospital
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getAverageRatingForSpecificHospital(Request $request)
    {
        $hospital = Hospital::find($request->hospital_id);
        if ($hospital) {
            $averageRating = HospitalReview::where('hospital_id', $hospital->id)
                ->select(DB::raw('ROUND(AVG(rating), 0) as avg_rating'))
                ->value('avg_rating');

            return response()->json([
                'status' => 'ok',
                'avgRating' => $averageRating
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => "There is no data for provided id#{$request->hospital_id}"
            ], 404);
        }
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
     * Remove the specified resource from storage.
     */
    public function destroy($hospital_id)
    {
        $hospital = Hospital::with('content')->find($hospital_id);
        if (!$hospital) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided hospital'
            ], 404);
        }
        $hospital->content()->delete();
        $hospital->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Hospital deleted successfully',
        ], 200);
    }
}
