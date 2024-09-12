<?php

namespace Database\Seeders;

use App\Models\Hospital\Hospital;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Hospital::factory(10)->create();
    }
}
