<?php

use Illuminate\Database\Seeder;

class ConsoleUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user1 = DB::table('console_users')->insert([
                		'first_name' => 'John',
				'last_name'  => 'Doe',
				'username'   => 'johnd',
				'email'      => 'johnd@exampleemail.com',
				'password'   => bcrypt('password'),
                		'company_id' => 1,
                		'remember_token' => str_random(10)

			]);

		DB::table('console_users')->insert([
                		'first_name' => 'Jane',
				'last_name'  => 'Doe',
				'username'   => 'janed',
				'email'      => 'janed@exampleemail.com',
				'password'   => bcrypt('password'),
               			 'company_id' => 1,
                		'remember_token' => str_random(10)

			]);

		DB::table('console_users')->insert([
                		'first_name' => 'John',
				'last_name'  => 'Smith',
				'username'   => 'johns',
				'email'      => 'johns@exampleemail.com',
				'password'   => bcrypt('password'),
              			 'company_id' => 1,
                		'remember_token' => str_random(10)
			]);
    }
}
