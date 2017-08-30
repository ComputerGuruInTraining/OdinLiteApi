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
		'dob' => date("2017-03-01 10:00:00"),
		'gender' => 'M',
		'mobile' => 111,
                'email'      => 'johnd@exampleemail.com',
		'password'   => bcrypt('secret'),
                'company_id' => 1,
                'remember_token' => str_random(10)
		]);
    
    
    
        //factory(App\User::class, 50)->create();
    }
}