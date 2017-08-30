<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AssignedShiftsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    //Austin, Texas
            DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 10:00:00'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 16:00:00'),
            'console_user_id' => 1,//manager who assigned shift
            'shift_title' => 'Airport Security Services',
            'shift_description' => 'Provide security services at the Executive Airport in Austin',
            'roster_id' => 2
        ]);
        
         DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 10:00:00'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 16:00:00'),
            'console_user_id' => 1,//manager who assigned shift
            'shift_title' => 'Security at Several Locations',
            'shift_description' => 'Provide security services at various locations throughout Austin',
            'roster_id' => 1
        ]);
        
       
        DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-23 23:00:00'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-24 7:00:00'),
            'console_user_id' => 1,
            'shift_title' => 'Event Security',
            'shift_description' => 'Sports Stadium',
            'roster_id' => 1
        ]);

        DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-17 12:00:00'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-17 18:00:00'),
            'console_user_id' => 1,
            'shift_title' => 'University Grounds Security',
            'shift_description' => 'Perform a security check at key locations in the University of Texas in Austin',
            'roster_id' => 1
        ]);
    
    //San Francisco
        //1 location, 3 employees
       /* DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 10:00:00', 'America/Chicago'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-04 16:00:00', 'America/Chicago'),
            'console_user_id' => 1,//manager who assigned shift
            'shift_title' => 'Bank of America',
            'shift_description' => 'Provide security services at the Bank of America',
            'roster_id' => 1
        ]);
        
        
        //4 locations, 2 employees
        DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-23 23:00:00', 'America/Chicago'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-24 7:00:00', 'America/Chicago'),
            'console_user_id' => 2,
            'shift_title' => 'Union Square Centre Security',
            'shift_description' => 'Perform a security check at the Union Square and surrounding areas',
            'roster_id' => 1
        ]);

//        3 locations, 2 employees
        DB::table('assigned_shifts')->insert([
            'start' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-17 12:00:00', 'America/Chicago'),
            'end' => Carbon::createFromFormat('Y-m-d H:i:s', '2017-03-17 18:00:00', 'America/Chicago'),
            'console_user_id' => 1,
            'shift_title' => 'Uni Grounds Security',
            'shift_description' => 'Perform a security check at the University of San Francisco grounds',
            'roster_id' => 1
        ]);
*/
        
    }
}