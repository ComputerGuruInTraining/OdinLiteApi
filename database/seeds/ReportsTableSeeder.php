<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    
    	DB::table('reports')->insert([
            'type' => 'Case Notes',
            'company_id' => 1,
            'date_start' => Carbon::createFromTimestamp(1488621600)->toDateTimeString(),//4th March 2017 @10am (time inc. here for accuracy testing purposes)
            'date_end' => Carbon::createFromTimestamp(1490461200)->toDateTimeString()//25th March 2017 @ 5pm
        ]);
        
        DB::table('reports')->insert([
            'type' => 'Case Notes',
            'company_id' => 2,
            'date_start' => Carbon::createFromTimestamp(1488621600)->toDateTimeString(),//4th March 2017 @10am (time inc. here for accuracy testing purposes)
            'date_end' => Carbon::createFromTimestamp(1490461200)->toDateTimeString()//25th March 2017 @ 5pm
        ]);
        
    /*
    	DB::table('reports')->insert([
            'type' => 'Case Notes',
            'date_start' => date('Y-m-d h:i:s', 1488621600),//4th March 2017 @10am (time inc. here for accuracy testing purposes)
            'date_end' => date('Y-m-d h:i:s', 1490461200)//25th March 2017 @ 5pm
        ]);
        
        
        DB::table('reports')->insert([
            'type' => 'Case Notes',
            'date_start' => date("2017-01-01"),
            'date_end' => date("2017-02-28")
        ]);
        
        
        DB::table('reports')->insert([
            'type' => 'Case Notes',
            'date_start' => date("2017-03-01 10:00:00"),
            'date_end' => date("2017-06-01 10:00:00")
        ]);
        */
    }
}