<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    
    /*
	$this->call(CaseNotesSeeder::class);
	$this->call(UserRolesTableSeeder::class);
	$this->call(LocationCompaniesTableSeeder::class);
	$this->call(CompaniesTableSeeder::class);
    */
	
       // $this->call(AssignedShiftEmployeesTableSeeder::class);
       // $this->call(AssignedShiftLocationsTableSeeder::class);
      
      //unique columns will return an error if locations already seeded
        //$this->call(LocationsTableSeeder::class);
        
        
        
       // $this->call(ReportCaseNotesTableSeeder::class);

	//$this->call(ReportCasesTableSeeder::class);
	
	
	//with dates
	$this->call(ReportsTableSeeder::class);
	/*
        $this->call(AssignedShiftsTableSeeder::class);
*/
	//$this->call(ConsoleUsersTableSeeder::class);

    }
}