<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ReportCasesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    
     DB::table('report_cases')->insert([
            'location_id' => 3,
            'total_hours' => 112,
            'total_guards' => 8,
            'report_id' => 1
        ]);
        
     DB::table('report_cases')->insert([
            'location_id' => 5,
            'total_hours' => 128,
            'total_guards' => 6,
            'report_id' => 2
        ]);
    /*
        DB::table('report_cases')->insert([
            'location_id' => 1,
            'total_hours' => 85.5,
            'total_guards' => 4,
            'report_id' => 1
        ]);
        
        
        DB::table('report_cases')->insert([
            'location_id' => 2,
            'total_hours' => 150,
            'total_guards' => 7,
            'report_id' => 2
        ]);
        */
    }
}