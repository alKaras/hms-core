<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Support\Str;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\Department\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Notifications\DoctorCredentialsNotification;

class DoctorImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        //Skip when row is null
        if (empty(array_filter($row))) {
            \Log::info('Skipping empty row:', $row);
            return null;
        }

        $password = Str::password($length = 12, $letters = true, $numbers = true, $symbols = true);

        $hospital = Hospital::whereHas('content', function ($query) use ($row) {
            $query->where('title', trim($row['hospital_title']));
        })->first();

        if (!$hospital) {
            throw new \Exception('Hospital not found: ' . $row['hospital_title']);
        }

        $user = User::where("email", trim($row["email"]))->first();

        if ($user === null) {
            $newUser = User::create([
                'email' => $row['email'],
                'name' => $row['name'],
                'surname' => $row['surname'],
                'phone' => $row['phone'],
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);

            $userRole = Role::where('title', 'user')->value('id');
            $doctorRole = Role::where('title', 'doctor')->value('id');

            if ($newUser) {
                $newUser->roles()->attach($userRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $newUser->roles()->attach($doctorRole, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $doctor = Doctor::create([
                'user_id' => $newUser->id,
                'specialization' => $row['specialization']
            ]);

            $newUser->update([
                'hospital_id' => $hospital->id
            ]);

            if (!empty($row['departments'])) {
                $departmentTitles = array_map('trim', explode(',', $row['departments']));

                $departments = Department::whereHas('content', function ($query) use ($departmentTitles) {
                    $query->whereIn('title', $departmentTitles);
                })->get();

                if ($departments->isNotEmpty()) {
                    $doctor->departments()->attach($departments, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $newUser->notify(new DoctorCredentialsNotification($newUser->email, $password));
            return $doctor;
        }

        $existedDoctor = Doctor::where('user_id', $user->id)->first();
        if ($existedDoctor) {
            throw new \Exception("The doctor for this user id {$user->id} has already been created");
        }


        if ((int) $user->hospital_id !== (int) $hospital->id && (int) $user->hospital_id !== null) {
            throw new \Exception("The user {$user->email} has already been linked to another hospital");
        }

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialization' => $row['specialization']
        ]);

        $user->update([
            'hospital_id' => $hospital->id
        ]);

        if (!empty($row['departments'])) {
            $departmentTitles = array_map('trim', explode(',', $row['departments']));

            $departments = Department::whereHas('content', function ($query) use ($departmentTitles) {
                $query->whereIn('title', $departmentTitles);
            })->get();

            if ($departments->isNotEmpty()) {
                $doctor->departments()->attach($departments, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $user->notify(new DoctorCredentialsNotification($user->email, $password));
        return $doctor;
    }
}
