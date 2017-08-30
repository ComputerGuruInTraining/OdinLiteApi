<?php

use Illuminate\Database\Seeder;

class UserRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user_roles')->insert([
            'role' => 'manager',//system wide privileges
            'console_user_id' => '1'
        ]);

        DB::table('user_roles')->insert([
            'role' => 'supervisor',//can create rosters and add locations but don't have access to odinmgmt settins (once in place) so can't remove the account etc.
            'console_user_id' => '2'
        ]);

        // DB::table('user_roles')->insert([
        //     'role' => 'user',//basic role if needed
        //     'console_user_id' => '1'
        // ]);
    }
}
