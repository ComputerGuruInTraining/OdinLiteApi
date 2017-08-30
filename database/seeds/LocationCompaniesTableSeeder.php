<?php

use Illuminate\Database\Seeder;

class LocationCompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
   
    public function run()
    {
        for($i=0; $i<9; $i++){
            DB::table('location_companies')->insert([
                'location_id' => $i+1,
                'company_id' => 1
            ]);
        }
    }
}
