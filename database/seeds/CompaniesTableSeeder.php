<?php

use Illuminate\Database\Seeder;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->insert([
            'name' => 'American Security Group',
            'description' => 'Provide Security Services across San Francisco',
            'owner' => 'John Doe'
        ]);

    }
}
