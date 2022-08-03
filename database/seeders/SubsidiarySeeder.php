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
            'description' => 'MÉXICO'
        ]);
        DB::table('subsidiaries')->insert([
            'description' => 'GUADALAJARA'
        ]);
    }
}
