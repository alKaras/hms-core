<?php

namespace Database\Seeders;

use App\Models\Hospital\HospitalContent;
use Illuminate\Database\Seeder;

class HospitalContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HospitalContent::factory(10)->create();
    }
}
