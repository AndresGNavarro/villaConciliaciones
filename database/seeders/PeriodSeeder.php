<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('periods')->insert([
            'reference' => 220301,
            'description' => '01-MAR-2022 to 08-MAR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220302,
            'description' => '09-MAR-2022 to 15-MAR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220303,
            'description' => '16-MAR-2022 to 23-MAR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220304,
            'description' => '24-MAR-2022 to 31-MAR-2022'
        ]);
        
        DB::table('periods')->insert([
            'reference' => 220401,
            'description' => '01-APR-2022 to 08-APR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220402,
            'description' => '09-APR-2022 to 15-APR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220403,
            'description' => '16-APR-2022 to 23-APR-2022'
        ]);
        DB::table('periods')->insert([
            'reference' => 220404,
            'description' => '24-APR-2022 to 30-APR-2022'
        ]);
    }
}
