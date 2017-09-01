<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $manager = DB::table('users')->insert([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'johnd@odin.com',
            'password'   => bcrypt('secret'),
            'company_id' => 1,
            'remember_token' => str_random(10)
		]);

        $manager = DB::table('users')->insert([
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
            'email'      => 'janes@odin.com',
            'password'   => bcrypt('secret'),
            'company_id' => 2,
            'remember_token' => str_random(10)
        ]);
    
    }
}