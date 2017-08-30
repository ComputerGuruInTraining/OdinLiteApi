<?php

use Illuminate\Database\Seeder;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    //Austin, Texas
        //single location
        DB::table('locations')->insert([
            'name' => 'Convention Center Austin',
            'address' => 'Austin Convention Center Austin, Texas, United States',
            'latitude' => '30.2635686',
            'longitude' => '-97.7396059',
            'notes' => 'Alarm Code is 1111'
        ]);

	//university of texas at austin locations
        DB::table('locations')->insert([
            'name' => 'Presidential Library',
            'address' => 'Presidential Library University of Texas at Austin, Austin, TX, United States',
            'latitude' => '30.2858226',
            'longitude' => '-97.7292611'
        ]);

        DB::table('locations')->insert([
            'name' => 'Concert Hall Uni Texas',
            'address' => 'Bass Concert Hall The University of Texas',
            'latitude' => '30.286095',
            'longitude' => '-97.7312361'
        ]);

        DB::table('locations')->insert([
            'name' => 'Mike Myers Stadium',
            'address' => 'Mike A. Myers Stadium, Clyde Littlefield Drive, University of Texas at Austin, Austin, TX, United States',
            'latitude' => '30.282659',
            'longitude' => '-97.7298965'
        ]);
        
        
        DB::table('locations')->insert([
            'name' => 'Downtown Austin',
            'address' => 'Downtown, Austin, Texas, United States',
            'latitude' => '30.2729209',
            'longitude' => '-97.7443863'
        ]);

        DB::table('locations')->insert([
            'name' => 'Barton Creek Sqr Austin',
            'address' => 'Barton Creek Square, Austin, Texas, United States',
            'latitude' => '30.2577142',
            'longitude' => '-97.806977'
        ]);
        
        DB::table('locations')->insert([
            'name' => 'Statesman Observation Center',
            'address' => 'Statesman Bat Observation Center Austin, Texas, United States',
            'latitude' => '30.2599308',
            'longitude' => '-97.7456007'
        ]);

        DB::table('locations')->insert([
            'name' => 'Planet Fitness Austin',
            'address' => 'Planet Fitness Austin, Texas, United States',
            'latitude' => '30.234191',
            'longitude' => '-97.7207'
        ]);      

        DB::table('locations')->insert([
            'name' => 'Austin Airport',
            'address' => 'Austin Executive Airport Aviation Drive',
            'latitude' => '30.3975028',
            'longitude' => '-97.5697162'
        ]);
        

	//San Francisco
        //3 University of San Francisco Locations
        DB::table('locations')->insert([
            'name' => 'Market Cafe, USF',
            'address' => 'The Market Cafe 2130 Fulton St, San Francisco, CA 94117, USA',
            'latitude' => '37.776565',
            'longitude' => '-122.450261',
            'notes' => 'Room 101'
        ]);

        DB::table('locations')->insert([
            'name' => 'John Lo Schiavo, USF',
            'address' => 'John Lo Schiavo, University Center, 2130 Fulton St, San Francisco, CA 94117, USA',
            'latitude' => '37.776247',
            'longitude' => '-122.45111'
        ]);

        DB::table('locations')->insert([
            'name' => 'McLaren Conference Center, USF',
            'address' => 'McLaren Conference Center, San Francisco, CA 94117, USA',
            'latitude' => '37.776004',
            'longitude' => '-122.449886'
        ]);

            //several locations, not closely situated
        DB::table('locations')->insert([
            'name' => 'Castro Theatre',
            'address' => 'Castro Theatre 429 Castro St, San Francisco, CA 94114, USA',
            'latitude' => '37.762033',
            'longitude' => '-122.434759'
        ]);

        DB::table('locations')->insert([
            'name' => 'Union Square',
            'address' => 'Union Square, San Francisco, CA 94108, USA',
            'latitude' => '37.787994',
            'longitude' => '-122.407437'
        ]);

        DB::table('locations')->insert([
            'name' => 'South of Market',
            'address' => 'South of Market, San Francisco, CA, USA',
            'latitude' => '37.778519',
            'longitude' => '-122.405639'
        ]);

        DB::table('locations')->insert([
            'name' => 'Ferry Building Marketplace',
            'address' => 'Ferry Building Marketplace, 1 Sausalito - San Francisco Ferry Bldg, San Francisco, CA 94111, USA',
            'latitude' => '37.795274',
            'longitude' => '-122.393421'
        ]);
    }
}