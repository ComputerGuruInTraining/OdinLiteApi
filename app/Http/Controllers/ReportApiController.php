<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Report as Report;
use App\ReportCases as ReportCase;
use App\ReportCheckCase as ReportCheckCase;
use App\ShiftCheckCases as CheckCases;
use App\ReportCaseNotes as ReportCaseNote;
use App\CaseNote as CaseNote;
use App\Cases as Cases;
use App\ShiftCheck as ShiftCheck;
use App\ShiftCheckCases as ShiftCheckCase;


class ReportApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function postCaseNotes(Request $request)
    {
    //get request data
    	$dateFrom = $request->input('dateFrom');
    	$dateTo = $request->input('dateTo');
    	$locId = $request->input('location');
    	$type = $request->input('type');
    	$compId = $request->input('compId');
    	
    	//convert the date strings to Carbon timestamps
    	$dateStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom);
    	$dateEnd =  Carbon::createFromFormat('Y-m-d H:i:s', $dateTo);
    	//$ts = $dateStart->timestamp();
    	
    	//insert into the Reports table
    	$report = new Report;
    	
    	$report->date_start = $dateStart;
    	$report->date_end = $dateEnd;
    	$report->company_id = $compId;
    	$report->type = $type;
    	
    	$report->save();
    	
    	$id = $report->id;
    	
    	//if($type == 'Case Notes'){
  //TODO: move to  a file yet to be created - functions.php
	
	//Retrieve the data for the location and date range needed to calculate totalHours and numGuards
	//NB: only retrieving shifts using shift_start_date, as ordinarily the shift_end_date would be the same or next day
	//get from table so that includes deletes from assigned_shifts so that the data can be provided in the report
	$shifts = DB::table('assigned_shift_locations')
	  ->join('shifts', 'shifts.assigned_shift_id', '=', 'assigned_shift_locations.assigned_shift_id')
	  ->where('assigned_shift_locations.location_id', '=', $locId)
	  ->whereBetween('shifts.start_time', [$dateStart, $dateEnd])
	  ->get();
	
	//calculate the total hours
	$totalMins = $shifts->sum('duration');//duration is in minutes
	$hours = $totalMins/60;
	$totalHours = floor($hours * 100) / 100;//hours to 2 decimal places
					
	//calculate the number of guards
	$numGuards = $shifts->count('mobile_user_id');
	
	//add to report_cases table
	
	$reportCase = new ReportCase;
	$reportCase->report_id = $id;
	$reportCase->location_id = $locId;
	$reportCase->total_hours = $totalHours;
	$reportCase->total_guards = $numGuards;
	$reportCase->save();
	$reportCaseId = $reportCase->id;
	
	$shiftIds = $shifts->pluck('id');	
	
	//retrieve the case_notes for the date range at the location
	//don't get the deleted case_notes
	$cases = CaseNote::whereIn('case_notes.shift_id', $shiftIds)->get();
	
	$caseIds = $cases->pluck('id');

	//add to  report_case_notes table
	
	foreach($caseIds as $caseId){
	    $reportNotes = new ReportCaseNote;
	    $reportNotes->report_case_id = $reportCaseId;
	    $reportNotes->case_note_id = $caseId;
	    $reportNotes->save();
	}
		
    	    
    //check to ensure a report was added which is certain (for type = Case Notes, report_case and report_case_note will only be added 
    //if shifts and notes respectively exist between the date range)
       if($id != null) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }
    
    public function postCasesAndChecks(Request $request)
    {
    	//get request data
    	$dateFrom = $request->input('dateFrom');
    	$dateTo = $request->input('dateTo');
    	$type = $request->input('type');
    	$compId = $request->input('compId');
    	$locId = $request->input('location');

    	//convert the date strings to Carbon timestamps
    	$dateStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom);
    	$dateEnd =  Carbon::createFromFormat('Y-m-d H:i:s', $dateTo);

    	//check to see if there were shifts for the location during the period
    	//otherwise will not create a report
    	$shifts = $this->queryReport($dateStart, $dateEnd, $locId);
    	
    	//shift_ids
    	$shiftIds = $shifts->pluck('id');
    	
    	//check to see if there are case notes for a report
    	//otherwise will not create a report
    	$caseNoteIds = $this->queryCaseNotes($locId, $shiftIds);
    	 
    	if(($shifts != null)&&($caseNoteIds != null)){
    		
	    	//insert into Reports table via function
	    	$result = $this->storeReport($dateStart, $dateEnd, $compId, $type);
	    
	    	//report saved and id returned in $result
	    	if($result->get('error') == null){

		    $reportId = $result->get('id');
		    
	            $resultCase = $this->storeReportCase($reportId, $shifts, $locId);
	            
	    	    if($resultCase->get('error') == null){
	            
	            	//variables needed to retrieve case_notes for the period and store in report_case_notes table
	            	$reportCaseId = $resultCase->get('reportCaseId');
	
	                $resultNote = $this->storeReportCaseNote($reportCaseId, $caseNoteIds);
	
	                	    if($resultNote->get('error') == null){
	                	    
	                	    	//$noteIds = $resultNote;
	                	    	$resultCheck = $this->storeReportCheckCase($reportCaseId, $caseNoteIds);
	                	        
	                	        if($resultCheck != 'no shift check'){
	
		                	    	return response()->json([
					                'success' => true,
					                'shiftChecks' => $resultCheck
					            ]); 
				        }else{
					        //no shift checks at the location
					        //ie in the case of a single location that does not have check in and check out
						return response()->json([
							'success' => true,
					                'shiftChecks' => $resultCheck//should equal 'no shift check'
						]);   
			                }
	
		           	    }else{
			               //error storing report case notes
				            return response()->json([
				                'success' => false
				            ]);   
			            }
			            return response()->json([
							'success' => $resultNote
						]);
	
	            }else{
	            //error storing report case
		            return response()->json([
		                'success' => false
		            ]);   
	            }
	
	            
	        }else{
	        //error storing report
	            return response()->json([
	                'success' => false
	            ]);   
	         }
 
         }else{
        //no shifts for the period at the location or no notes for the location during the shifts
            return response()->json([
                'success' => false
            ]);   
         }      
    }
    
    public function storeReportCheckCase($reportCaseId, $noteIds)
    {
    	//retrieve from ShiftCheckCases the records for those shift_checks
    	//ie all checks and the case notes created during the checks for the shifts
    	$caseChecks = ShiftCheckCase::whereIn('case_note_id', $noteIds)->get();
    
	//ensure there are shift_checks. Single Location Shifts will not have entries in the shift_checks or shift_check_cases tables
        if($caseChecks->isNotEmpty()){
                //shift_check_case_ids
    		$caseChecksId = $caseChecks->pluck('id');

	        //add to shift_check_cases table
		foreach($caseChecksId as $caseCheckId){
			$reportChecks = new ReportCheckCase;
			$reportChecks->report_case_id = $reportCaseId;
			$reportChecks->shift_check_case_id = $caseCheckId;
			$reportChecks->save();
		}
		//FIXME: causing an error in no shift checks
		if($reportChecks->save()){
	       		 return $caseChecksId;
    		}
    	}else{  
	           $msg = 'no shift check';
	    	   return $msg;
    	}
    } 

    public function storeReportCaseNote($reportCaseId, $caseNoteIds)
    {
	//add to report_case_notes table
	foreach($caseNoteIds as $caseNoteId){
		$reportNotes = new ReportCaseNote;
		$reportNotes->report_case_id = $reportCaseId;
		$reportNotes->case_note_id = $caseNoteId;
		$reportNotes->save();
	}

		
	$error = array('error' => 'error');
    	
	if($reportNotes->save()){
    	    return $caseNoteIds;
    	}else{
    	    return $error;
    	}

    }
    
    public function storeReport($dateStart, $dateEnd, $compId, $type){
    
    	//insert into the Reports table
    	$report = new Report;
    	
    	$report->date_start = $dateStart;
    	$report->date_end = $dateEnd;
    	$report->company_id = $compId;
    	$report->type = $type;
    	
    	$report->save();
    	
    	$id = $report->id;
    	
    	$reportData = collect(['id' => $id]);
    	$error = collect(['error' => 'error']);
    	
    	if($report->save()){
    	    return $reportData;
    	}else{
    	    return $error;
    	}
    }
    
    public function storeReportCase($reportId, $shifts, $locId)
    {
	    	$shiftIds = $shifts->pluck('id');

		//calculate the total hours
		$totalMins = $shifts->sum('duration');//duration is in minutes
		$hours = $totalMins/60;
		$totalHours = floor($hours * 100) / 100;//hours to 2 decimal places
						
		//calculate the number of guards
		$numGuards = $shifts->count('mobile_user_id');
		
		//add to report_cases table
		
		$reportCase = new ReportCase;
		$reportCase->report_id = $reportId;
		$reportCase->location_id = $locId;
		$reportCase->total_hours = $totalHours;
		$reportCase->total_guards = $numGuards;
		$reportCase->save();
		
		$reportCaseId = $reportCase->id;
	
		$data = collect(['reportCaseId' => $reportCaseId]);
		$error = collect(['error' => 'error']);

	if($reportCase->save()){
    	    return $data;
    	}else{
    	    return $error;
    	}
		
    }

    public function getCasesAndChecks($id){
        try {

            //$reportCaseId will hold just one value
            $reportCaseId = $this->getTable2Id('report_cases', $id, 'report_id');

            $reportChecks = DB::table('report_check_cases')
                //single value to join on
                ->join('shift_check_cases', function ($join) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('shift_check_cases.id', '=', 'report_check_cases.shift_check_case_id');
//                        ->where('case_notes.deleted_at', '=', null);
                })
                ->join('report_cases', function ($join) use ($reportCaseId) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('report_cases.id', '=', 'report_check_cases.report_case_id')
                        ->where('report_check_cases.report_case_id', '=', $reportCaseId);
                })
//                ->where('report_case_notes.deleted_at', '=', null)
                ->select('shift_check_cases.*', 'report_cases.*')
//                ->orderBy('case_notes.created_at')
                ->get();

                //get employee's name
//            $caseUsers = $reportCaseNotes->pluck('user_id');
                
            //need to display the employee first name and last name even if the employee
            //has been deleted since taking the case note, so get withTrashed
//            $employees = User::withTrashed()->whereIn('id', $caseUsers)->get();
//
//            if ($employees != null) {
//                foreach ($reportCaseNotes as $i => $item) {
//
//                    foreach ($employees as $employee) {
//                        if ($reportCaseNotes[$i]->user_id == $employee->id) {
//
//                            //store location name in the object
//                            $reportCaseNotes[$i]->employee = $employee->first_name . ' ' . $employee->last_name;
//                        }
//                    }
//
//                }
//            }

            // retrieve location name using location_id from reportCases using table so as to still retrieve data if the location has been deleted.
//            $location = DB::table('locations')
//                ->where('id', '=', $reportCases->location_id)
//                ->get()
//                ->first();

            return response()->json([
                'reportChecks' => $reportChecks,
//                'reportCaseNotes' => $reportCaseNotes,
//                'reportCases' => $reportCases,
//                'location' => $location,
                'success' => true
            ]);
        } //error thrown if no report_case record for a report_id ie when no shift falls within the date range for a location
        catch (\ErrorException $e) {
            return response()->json([
                'success' => false
            ]);
        }

    }

    //generic get id from table using id from another table
    public function getTable2Id($table2, $table1id, $column)
    {
        $records = DB::table($table2)
            ->where($column, '=', $table1id)
            ->where('deleted_at', '=', null)
            ->first();

        $table2Id = $records->id;

        return $table2Id;

    }
    
    //returns a collection or array?? of shifts
    public function queryReport($dateStart, $dateEnd, $locId)
    {
    	//Retrieve the data for the location and date range needed to calculate totalHours and numGuards
	//NB: only retrieving shifts using shift_start_date, as ordinarily the shift_end_date would be the same or next day
	//get from table so that includes deletes from assigned_shifts so that the data can be provided in the report
	$shifts = DB::table('assigned_shift_locations')
	  ->join('shifts', 'shifts.assigned_shift_id', '=', 'assigned_shift_locations.assigned_shift_id')
	  ->where('assigned_shift_locations.location_id', '=', $locId)
	  ->whereBetween('shifts.start_time', [$dateStart, $dateEnd])
	  ->get();
	  
	return $shifts;
    } 
    
    public function queryCaseNotes($locId, $shiftIds)
    {   
	//retrieve non-deleted case_notes that match the location and shifts in question
	//quirk: errors when trying to retrieve case_notes.id therefore subsequent query to get this data
	$notes = DB::table('case_notes')
	  ->join('cases', 'case_notes.case_id', '=', 'cases.id')
	  ->where('cases.location_id', '=', $locId)
	  ->whereIn('case_notes.shift_id', $shiftIds)//whereIn equal to an array of shiftIds
	  ->where('cases.deleted_at', '=', null)
	  ->where('case_notes.deleted_at', '=', null)
	  ->get();
	  
	 //these ids will be the case_ids for the query results (not the case_note_ids)
	$caseIds = $notes->pluck('case_id');	

	$caseNotes = CaseNote::whereIn('case_id', $caseIds)->get(); 
		 		
	$caseNoteIds = $caseNotes->pluck('id');
	
	return $caseNoteIds;
    }

}
