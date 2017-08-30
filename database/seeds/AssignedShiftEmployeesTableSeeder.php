<?php

use Illuminate\Database\Seeder;

class AssignedShiftEmployeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 1,
            'assigned_shift_id' => 1
        ]);

        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 2,
            'assigned_shift_id' => 1
        ]);

        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 4,
            'assigned_shift_id' => 1
        ]);

        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 2,
            'assigned_shift_id' => 2
        ]);
        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 1,
            'assigned_shift_id' => 2
        ]);
        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 2,
            'assigned_shift_id' => 3
        ]);

        DB::table('assigned_shift_employees')->insert([
            'mobile_user_id' => 4,
            'assigned_shift_id' => 3
        ]);
    }
}
