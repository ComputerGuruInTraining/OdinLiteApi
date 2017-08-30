<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ReportCaseNotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	//company 1 as at 17th July, as data for case_notes with the included ids is for company 1 as per mobile_user_id
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 1,
	            'case_note_id' => 13
	    ]);
	    
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 1,
	            'case_note_id' => 14
	    ]);
	    
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 1,
	            'case_note_id' => 15
	    ]);
	    
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 1,
	            'case_note_id' => 16
	    ]);
	    
	    //company 2 as at 17th July
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 2,
	            'case_note_id' => 8
	    ]);
	    
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 2,
	            'case_note_id' => 12
	    ]);
	    
	    DB::table('report_case_notes')->insert([
	            'report_case_id' => 2,
	            'case_note_id' => 11
	    ]);
        
        /*
        DB::table('report_case_notes')->insert([
            'report_case_id' => 3,
            'case_note_id' => 1
        ]);
        
        
        DB::table('report_case_notes')->insert([
            'report_case_id' => 1,
            'case_note_id' => 1
        ]);
        */
    }
}