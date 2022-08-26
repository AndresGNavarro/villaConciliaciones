<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubsidiarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subsidiaries')->insert([
            'description' => 'MÉXICO',
            'iata' => '86515984'
        ]);
        DB::table('subsidiaries')->insert([
            'description' => 'GUADALAJARA',
            'iata' => '86502194'
        ]);
        DB::table('subsidiaries')->insert([
            'description' => 'RAYON',
            'iata' => '86511574'
        ]);
        DB::table('subsidiaries')->insert([
            'description' => 'CONDUCTORES',
            'iata' => '86515973'
        ]);
    }
}
