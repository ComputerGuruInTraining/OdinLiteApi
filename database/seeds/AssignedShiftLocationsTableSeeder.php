<?php

use Illuminate\Database\Seeder;

class AssignedShiftLocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('assigned_shift_locations')->insert([
            'location_id' => 1,
            'assigned_shift_id' => 1,
            'checks' => 1
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 2,
            'assigned_shift_id' => 2,
            'checks' => 5
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 3,
            'assigned_shift_id' => 2,
            'checks' => 5
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 4,
            'assigned_shift_id' => 2,
            'checks' => 5
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 5,
            'assigned_shift_id' => 3,
            'checks' => 3
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 6,
            'assigned_shift_id' => 3,
            'checks' => 3
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 7,
            'assigned_shift_id' => 3,
            'checks' => 3
        ]);

        DB::table('assigned_shift_locations')->insert([
            'location_id' => 8,
            'assigned_shift_id' => 3,
            'checks' => 3
        ]);
    }
}
