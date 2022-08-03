<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('document_types')->insert([
            'description' => 'IATA'
        ]);
        DB::table('document_types')->insert([
            'description' => 'PREVIO'
        ]);
        DB::table('document_types')->insert([
            'description' => 'RESUMEN'
        ]);
        DB::table('document_types')->insert([
            'description' => 'ANÁLISIS'
        ]);
    }
}
