<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ShiftCheckCases as CheckCases;
use App\ShiftCheck as ShiftCheck;
use Carbon\Carbon;
use App\AssignedShift as AssignedShift;
use App\AssignedShiftEmployee as AssignedEmp;
use App\AssignedShiftLocation as AssignedLoc;
use App\Location as Location;
use App\User as User;
use App\ShiftResume as ShiftResume;


class JobsController extends Controller
{

    //note, in return collection $id = assigned_location_id
    public function getAssignedShiftsList($compId){

        $assigned = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('assigned_shifts.company_id', '=', $compId)
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->orderBy('start', 'asc')
            ->orderBy('assigned_shift_locations.location_id')
            ->get();

        foreach ($assigned as $i => $item) {

            //find the location_id name if a location exists for that id in the locations table
            $location = Location::find($assigned[$i]->location_id);

            if ($location != null) {

                $name = $location->name;

                //store location name in the object
                $assigned[$i]->location = $name;
            }
        }

        foreach ($assigned as $i => $details) {
            //get the employee's name from the employees table for all employees assigned a shift
            $emp = User::find($assigned[$i]->mobile_user_id);
            //ensure the assigned_shift_employee record exists in the users table else errors could occur
            if ($emp != null) {

                $first_name = $emp->first_name;
                $last_name = $emp->last_name;
                $name = $first_name . ' ' . $last_name;

                //store location name in the object
                $assigned[$i]->employee = $name;
            } else {
                //$emp == null meaning it must have been deleted from the employees table
                //Step: soft delete the record from assigned_shift_employees table for the employee's user_id
                //to ensure there are no shifts assigned to a user that has been deleted
                //first, find the array of records that have the mobile_user_id in question
                $assignedEmps = AssignedEmp::where('mobile_user_id', '=', $assigned[$i]->mobile_user_id)->get();
                //then loop through those records, soft deleting each model
                foreach ($assignedEmps as $assignedEmp) {
                    $assignedEmp->delete();
                }

                //Step: having deleted it from the table, we now need to remove the object from the $assigned array
                $assigned->pull($i);
            }
        }

        //find the assigned_shifts that have an entry in the shifts table, and retrieve the user_id of those shifts
        foreach ($assigned as $i => $assignedShift) {

            $commenced = DB::table('shifts')
                ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
                ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
                ->where('assigned_shifts.id', '=', $assignedShift->assigned_shift_id)
                ->select('shifts.mobile_user_id')
                ->get();

            if(count($commenced) == 0){
                $assigned[$i]->commenced = 'not commenced';
            }else if (count($commenced) > 0){

                $assigned[$i]->commenced = 'commenced';
            }
        }

        //Step: reset the keys on the collection, else datatype is std::class
        $assigned = $assigned->values();

        return response()->json($assigned);
    }

    //getAssignedShifts for a particular userId
    public function getAssignedShifts($id){
        //the logic is:
        //step 1: all assignedShifts for the period. (array1)
        //step 2: all assignedShifts that have been started by the mobile user (array2)
        // (!Important! > 1 mobile user can be assigned to shift)
        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array4)
        //step 4: all assignedShifts that have been started, check if they have ended (array3)
        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)
        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)

        //step 1: all assigned shifts
        $array1 = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=',
                'assigned_shifts.id')
            ->select('assigned_shifts.id')
            ->where('assigned_shift_employees.mobile_user_id', '=', $id)
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 2 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->get();

        //all assigned shifts for the period specified
        $array1ids = $array1->pluck('id');

        //step2 :all shifts that have been started by this mobile_user
        $array2ids = DB::table('shifts')
            ->whereIn('assigned_shift_id', $array1ids)
            ->where('mobile_user_id', '=', $id)
            ->pluck('assigned_shift_id');

        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array1-2)
        //1st set of data
        $array4 = $array1ids->diff($array2ids);

        //step4: all shifts out of the shifts that have been started and have been completed
        $array3ids = DB::table('shifts')
            //array of ids
            ->whereIn('assigned_shift_id', $array2ids)
            ->where('end_time', '!=', null)
            ->where('mobile_user_id', '=', $id)
            ->pluck('assigned_shift_id');

        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)
        $array5 = $array2ids->diff($array3ids);

        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)
        $array6ids = $array4->merge($array5);

        $myAssigned = DB::table('assigned_shift_employees')
            ->join('assigned_shifts', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->whereIn('assigned_shifts.id', $array6ids)
            ->where('mobile_user_id', '=', $id)
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->get();

        foreach ($myAssigned as $i => $assigned) {
            //convert start and end from a datetime object to timestamps
            //and append to the end of all of the assigned objects
            $myAssigned[$i]->start_ts = strtotime($assigned->start);
            $myAssigned[$i]->end_ts = strtotime($assigned->end);
        }

        return response()->json($myAssigned);

    }

    public function getCommencedShifts($mobileuserid){

        $myCommenced = DB::table('assigned_shifts')
            ->join('shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->select('assigned_shifts.id', 'shifts.start_time')
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 2 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('shifts.mobile_user_id', '=', $mobileuserid)
            ->where('shifts.end_time', '=' , null)//shift has not ended
            ->where('shifts.deleted_at', '=', null)//ensuring shift has not been deleted
            ->orderBy('shifts.start_time', 'desc')//the most recent first
            ->get();

        //returns a result set of assigned_shift_ids or null
        return response()->json($myCommenced);
    }

    public function getCommencedShiftDetails($assignedid, $mobileUserId)
    {
        //Step 1: get the shift_locations
        //each assigned object will have:
        //location_id, name, address, latitude, longitude, notes, [required] checks
        //result set is a collection, whether 1 or many
        //Data Requirement 1
        $assignedLoc = $this->getShiftLocations($assignedid);

        //Step 2: get the shift_id that corresponds to the assigned_shift_id
        //each assigned_shift_id will only have 1 shift_id for a particular user
        //but retrieve first, because technically possible to have more than 1 from db query point of view
        //result is an array even though only one.
        $shiftId = $this->getShiftId($assignedid, $mobileUserId);

        //todo: implement at a later date as needs to be thorough or there will be errors
        //returns details such as the shiftCheckId for each location and location_id
        $checkId = $this->getCurrentCheckIn($shiftId->id);

        $countChecksWoCheckOut = count($checkId);

        foreach ($assignedLoc as $i => $location) {

            //Data Requirement 2. number of checks completed
            $numChecks = $this->countShiftChecks($shiftId->id, $location->location_id);

            $assignedLoc[$i]->checked = $numChecks;

        }

        //ensure that the latest check in started by the user was not started before another shift was started or resumed
        //we can keep the location checked in if not,
        // but if there has been another shift started or resumed since, we won't keep the location checked in
        //value will be null if doesn't meet our conditions
        //or if does meet our conditions an object with shift_id, shift_checks.id, check_ins, check_outs, location_id, shift_checks.created_at
        $latestCheck = $this->getLatestShiftCheckResume($mobileUserId);

        $caseCheck = false;//initialise

        if (count($assignedLoc) > 1) {

            foreach ($assignedLoc as $i => $location) {

                //default
                $assignedLoc[$i]->latestCheckIn = false;
                $assignedLoc[$i]->currentCheckIn = null;
                $assignedLoc[$i]->checkedIn = false;
                $assignedLoc[$i]->casePerCheck = false;

                //if the check in relates to that location, assign relevant value
                foreach ($checkId as $j => $checkIdItem) {
                    if ($assignedLoc[$i]->location_id == $checkId[$j]->location_id) {

                        $assignedLoc[$i]->checkedIn = true;
                        $assignedLoc[$i]->currentCheckIn = $checkId[$j]->id;

                        //Data Requirement 4:
                        $casePerCheck = $this->caseNoteSbmtd($checkId[$j]->id);

                        //if a case note exists for the current check in
                        if (count($casePerCheck) > 0) {
                            $assignedLoc[$i]->casePerCheck = true;
                        }

                        //this check in is the latest for the user, and they haven't started or resumed another shift since this check in
                        if($latestCheck != null) {
                            if ($checkId[$j]->id == $latestCheck->id) {
                                $assignedLoc[$i]->latestCheckIn = true;

                                //if a case note exists for the current check in
                                if (count($casePerCheck) > 0) {
                                    $caseCheck = true;
                                }

                                break;
                            }

                        }
                    }
                }

            }
        } else if (count($assignedLoc) == 1) {
            //single locations
            //data required is: location details, has a case note been submitted,
            //ATM, before more testing is complete, reluctant to remove this working code for single location shifts,
            //so keep it for the moment


            //default
            $assignedLoc[0]->latestCheckIn = false;
            $assignedLoc[0]->checkedIn = false;
            $assignedLoc[0]->currentCheckIn = null;
            $assignedLoc[$i]->casePerCheck = false;
            $caseCheck = false;

//            commencedshiftdetails/2124/2104
                //2124/2084
                foreach ($checkId as $x => $checkIdItem) {
                    if ($assignedLoc[0]->location_id == $checkId[$x]->location_id) {

                        $assignedLoc[0]->checkedIn = true;
                        $assignedLoc[0]->currentCheckIn = $checkId[$x]->id;

                        //this check in is the latest for the user, and they haven't started or resumed another shift since this check in
                        if($latestCheck != null) {
                            if ($checkId[$x]->id == $latestCheck->id) {
                                $assignedLoc[0]->latestCheckIn = true;

                                $notes = app('App\Http\Controllers\CaseNoteApiController')->getShiftCaseNotes($shiftId->id);

                                if (count($notes) > 0) {
                                    $caseCheck = true;
                                    //single loc commencedshiftdetails/2124/2084
                                }

                                break;
                            }
                        }
                    }
                }
        }

        //store the resume shift in the shiftResumeTable
        $shiftResumeId = $this->storeShiftResume('resume', $shiftId->id);

        return response()->json([
            'locations' => $assignedLoc,
            'shiftId' => $shiftId,
            'caseCheck' => $caseCheck,
            'shiftResumeId' => $shiftResumeId,//value or null if storeError
            'countChecksWoCheckOut' => $countChecksWoCheckOut
        ]);
    }

    public function getLatestShiftCheckResume($mobileUserId){

        $resumes = null;
        $latestCheckIn = true;

        //get the latest shift check for the user, whether checked out or not
        $check = $this->latestShiftCheck($mobileUserId);//either an object or null

        //get all the shift resumes > the latest check in created_at times
        if($check != null) {
            $resumes = $this->latestShiftResumes($mobileUserId, $check->created_at);

            //loop through the resumes array
            //if any of the shift_ids do not equal the shift id of the latest_check
            //then another shift has started or resumed after the check in
            //so just return null and do not keep the location checked in on the mobile side
            if(sizeof($resumes) > 0) {
                foreach ($resumes as $i => $resume) {

                    //if any of the $resumes array has a value not equal to the check in shift id
                    if ($resume->shift_id != $check->shift_id) {
                        $latestCheckIn = false;
                    }
                }
            }
        }

//        dd($check, $latestCheckIn);

        if($latestCheckIn == true){

            return $check;

        }else{

            return null;
        }
    }

    //returns array or null?
    public function latestShiftResumes($mobileUserId, $createdAt){

        $resumes = DB::table('shift_resumes')
            ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
            ->select('shift_resumes.shift_id', 'shift_resumes.created_at', 'shifts.mobile_user_id')
            ->where('shifts.mobile_user_id', '=', $mobileUserId)
            ->where('shift_resumes.created_at', '>', $createdAt)
            ->get();


        return $resumes;


    }

    //get the latest shift check for the user, whether checked out or not
    public function latestShiftCheck($mobileUserId){

        $check = DB::table('shift_checks')
            ->join('shifts', 'shifts.id', '=', 'shift_checks.shift_id')
            ->select('shift_checks.shift_id', 'shift_checks.id', 'shift_checks.check_ins', 'shift_checks.check_outs', 'shift_checks.location_id', 'shift_checks.created_at')
            ->where('shifts.mobile_user_id', '=', $mobileUserId)
            ->latest()
            ->first();

        return $check;

    }

    /*    public function checkShiftLastStartedForUser($mobileUserId, $shiftId){


            $mobileUserId = 2014;
            $shiftId = 5514;//5514 is the last shift resumed by the user and there are 2 records before 5504
            //if shiftId = 5524, the $results returns the last shift started by the user as it wasn't the shiftId being passed in.

            //Step: find the next record where not equal to the shiftId we pass in
            $results = DB::table('shift_resumes')
                ->select('*', 'shift_resumes.id as shift_resumes_id')
                ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
                ->where('shifts.mobile_user_id', '=', $mobileUserId)
                ->where('shift_resumes.shift_id', '!=', $shiftId)
                ->orderBy('shift_resumes.created_at', 'desc')
                ->first();

            //Step: we need to retrieve the first record before the $results record
            $res = DB::table('shift_resumes')
                        ->where('id', '>', $results->shift_resumes_id)
                        ->orderBy('id','desc')
                        ->first();

            $shiftLastStarted = null;

            //Step: check if the first record before the shiftId value changes (for the user) has a status of start
            //if so, we can deduce that the last shift started was the shiftId we pass through
            //therefore, we can keep the user checked into the location
            if($res != null) {
                if ($res->status == 'start') {

                    $shiftLastStarted = $res->shift_id;
                }
            }

            dd($results, $res, $shiftLastStarted);

        }*/

    public function getShiftLocations($asgnshftid){

        $assigned = DB::table('assigned_shift_locations')
            ->join('locations', 'locations.id', '=', 'assigned_shift_locations.location_id')
            ->where('assigned_shift_locations.assigned_shift_id', '=', $asgnshftid)
            ->where('locations.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->get();

        return $assigned;
    }

    //ensure get the most recent shift that relates to an assigned_shift_id,
    // mostly useful in development as shouldn't be a factor in production but just in case
    //as all shift data used within mobile for commenced shifts relies upon the shiftId being accurate
    public function getShiftId($assignedId, $mobileUserId){

        $shiftIdObject = DB::table('shifts')
            ->select('id')
            ->where('assigned_shift_id', '=', $assignedId)
            ->where('mobile_user_id', '=', $mobileUserId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $shiftIdObject;
    }

    //get shift checks amount completed for a particular shift_id per location
    public function countShiftChecks($shiftId, $locationId){

        $numChecks = DB::table('shift_checks')
            ->where('shift_id', '=', $shiftId)
            ->where('location_id', '=', $locationId)
            ->where('check_outs', '!=',  null)
            ->count();

        return $numChecks;
    }

    //return: should only be one value
    //it is possible that there may be more than one checkIn without a checkOut
    public function getCurrentCheckIn($shiftId){

        $checkInProgress = DB::table('shift_checks')
            ->select('id', 'check_ins', 'check_outs', 'location_id', 'created_at')
            ->where('shift_id', '=', $shiftId)
            ->where('check_outs', '=',  null)
            ->get();

        return $checkInProgress;
    }

    public function caseNoteSbmtd($checkIn)
    {

        $shiftCheckCase = DB::table('shift_check_cases')
            ->where('shift_check_id', '=', $checkIn)
            ->first();

        return $shiftCheckCase;
    }

   //insert into shift_check_cases table
    public function postShiftCheckCase($caseNoteId, $sftChkId){

        $sftChkCase = new CheckCases;
        $sftChkCase->case_note_id = $caseNoteId;
        $sftChkCase->shift_check_id = $sftChkId;

        if($sftChkCase->save()){
            $id = $sftChkCase->id;

            return $id;
        }else{

            return 0;
        }
    }

    //mobile: Called to store the shift check_in in the shift_checks table
    //and the shiftResumeTable
    public function storeCheckIn($request)
    {
        //use posId to get the latitude and the longitude of the geoLocation
        //and compare with the location of the shiftCheck to determine if withinRange
        $posId = $request->input('posId');
        $locId = $request->input('locId');

        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);

        $shiftCheck = new ShiftCheck;

        //$shift->check_ins will take the default current_timestamp
        $shiftCheck->shift_id = $request->input('shiftId');
        $shiftCheck->user_loc_check_in_id = $posId;
        $shiftCheck->location_id = $locId;//note: ts is in UTC time
        $shiftCheck->checks = $request->input('checks');
        $shiftCheck->distance_check_in = $distance;
        $shiftCheck->save();

//        $createdAt = $shiftCheck->created_at;

        //retrieve id of the saved shift
        $id = $shiftCheck->id;

        return $id;
    }

    //mobile: store the location check out
    public function storeCheckOut($request)
    {
        //retrieve current shift's record for update
        //using shift_id and location_id where check_outs null
        $checkId = $request->input('shiftChecksId');
        $check = ShiftCheck::find($checkId);

        $posId = $request->input('posId');
        $locId = $check->location_id;

        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);

        $checkOut = Carbon::now();
        $checkInTime = $check->check_ins;

        //calculate checkDuration
        $checkDuration = checkDuration($checkInTime, $checkOut);

        $check->user_loc_check_out_id = $posId;
        $check->check_outs = $checkOut;
        $check->distance_check_out = $distance;
        $check->check_duration = $checkDuration;//seconds

        if ($check->save()) {
            return 'success';
        } else {
            return 'failed';
        }
    }

    //returns a single record or an array of records as the assigned shift may have multiple entries in the
    // assigned_shift_locations and/or assigned_shift_employees tables
    public function getAssignedShift($id){

        //verify company first
        $assignedObject = AssignedShift::find($id);

        $verified = verifyCompany($assignedObject);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //if verified as being the same company, or if no record is returned from the query ie $assigned = {}
        $assigned = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('assigned_shifts.id', '=', $id)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->orderBy('start', 'asc')
            ->orderBy('assigned_shift_locations.location_id')
            ->get();

        foreach ($assigned as $i => $details) {
            $emp = User::find($assigned[$i]->mobile_user_id);

            //ensure the assigned_shift_employee record exists in the users table
            if ($emp != null) {
                $first_name = $emp->first_name;
                $last_name = $emp->last_name;
                $name = $first_name . ' ' . $last_name;
            } //mobile_user_id does not exist in locations table
            else {
                $name = "Employee not in database";
            }
            //store location name in the object
            $assigned[$i]->employee = $name;
        }

        foreach ($assigned as $i => $item) {

            //find the location_id name if a location exists for that id in the locations table
            $location = Location::find($assigned[$i]->location_id);

            if ($location != null) {
                $name = $location->name;
            } //location_id does not exist in locations table
            else {
                $name = "Location not in database";
                $assigned[$i]->checks = 0;
            }
            //store location name in the object
            $assigned[$i]->location = $name;
        }

        //find whether this assigned_shift has an entry in the shifts table, and retrieve the user_id of those shifts
        foreach ($assigned as $i => $assignedShift) {

            $commenced = DB::table('shifts')
                ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
                ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
                ->where('assigned_shifts.id', '=', $assignedShift->assigned_shift_id)
                ->select('shifts.mobile_user_id')
                ->get();

            if(count($commenced) == 0){
                $assigned[$i]->commenced = 'not commenced';

            }else if (count($commenced) > 0){

                $assigned[$i]->commenced = 'commenced';
            }
        }

        return response()->json($assigned);
    }

    public function putShift(Request $request, $id){
        //table: assigned_shifts

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start'));
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('end'));

        $assigned = AssignedShift::find($id);

        //verify company first
        $verified = verifyCompany($assigned);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        $assigned->company_id = $request->input('compId');

        if ($request->has('title')) {
            $assigned->shift_title = $request->input('title');
        }

        if ($request->has('desc')) {
            if($request->input('desc') == "none") {
                $assigned->shift_description = null;
            }else{
                $assigned->shift_description = $request->input('desc');
            }
        }

        if ($request->has('roster_id')) {
            $assigned->roster_id = 1;
        }

        $assigned->start = $start;
        $assigned->end = $end;

        $assigned->asg_duration_mins = $start->diffInMinutes($end);

        $assigned->save();

        //assigned_shift_employees
        //update the employees assigned to the shift

        //new employee values to be used for updating the table
        $newEmpArray = $request->input('employees');

        $newEmp = collect($newEmpArray);

        //current employee values to be replaced with the new edits that haven't already been deleted by way of deleting employee
        $oldEmps = DB::table('assigned_shift_employees')
            ->where('assigned_shift_id', '=', $id)
            ->where('deleted_at', '=', null)
            ->pluck('mobile_user_id');

        /*
        **compare the old employees to the new employees
        **in assigned_shift_employees table
        */

        //create new records for those new employees updated in the view that aren't already in the assigned_shift_employees table
        $addEmps = $newEmp->diff($oldEmps);

        foreach ($addEmps as $addEmp) {
            $employee = new AssignedEmp;
            $employee->mobile_user_id = $addEmp;
            $employee->assigned_shift_id = $id;
            $employee->save();
        }

        //delete those records that are currently in the assigned_shift_employees table but were not included in the edit
        $deleteEmps = $oldEmps->diff($newEmp);

        foreach ($deleteEmps as $deleteEmp) {
            $assignedEmp = AssignedEmp::where('mobile_user_id', '=', $deleteEmp)
                ->where('assigned_shift_id', '=', $id)
                ->delete();

        }

        //might fixme be best to include an object with the location and check, not just an array which is separate
        $checksArray = $request->input('checks');
        //assigned_shift_locations
        //update the locations assigned to the shift

        $newLocArray = $request->input('locations');//from 664, 714

        //for location checks
        $colLocCheck = collect([]);

        for($l = 0; $l < sizeof($newLocArray); $l++){

            $array = array_add(['location' => $newLocArray[$l]], 'checks', $checksArray[$l]);

            $colLocCheck->push($array);
        }

        $newLoc = collect($newLocArray);

        //current old assigned_location values to be edited that have not otherwise been deleted
        $oldLocs = DB::table('assigned_shift_locations')
            ->where('assigned_shift_id', '=', $id)
            ->where('deleted_at', '=', null)
            ->pluck('location_id');

        //create new records for those new locations updated in the view that aren't already in the assigned_shift_locations table
        $addLocs = $newLoc->diff($oldLocs);

        foreach ($addLocs as $addLoc) {
            //before adding the location, find the appropriate collection item with the corresponding checks
            foreach($colLocCheck as $colCheck){

                if($addLoc == $colCheck['location']){

                    $location = new AssignedLoc;
                    $location->location_id = $addLoc;
                    $location->assigned_shift_id = $id;
                    $location->checks = $colCheck['checks'];
                    $location->save();
                }
            }
        }

        //delete those records that are currently in the assigned_shift_locations table but were not included in the edit
        $deleteLocs = $oldLocs->diff($newLoc);

        //soft delete on model
        foreach ($deleteLocs as $deleteLoc) {
            $assignedLoc = AssignedLoc::where('location_id', '=', $deleteLoc)
                ->where('assigned_shift_id', '=', $id)
                ->delete();
        }

        //update those records that have the same location_id and assigned_shift_id, but a different amount of checks
        $sameLocs = $oldLocs->intersect($newLoc);

        foreach ($sameLocs as $sameLoc) {
            $toUpdateId = DB::table('assigned_shift_locations')
                ->where('location_id', '=', $sameLoc)
                ->where('assigned_shift_id', '=', $id)
                ->where('deleted_at', '=', null)
                ->pluck('id');

            $location = AssignedLoc::find($toUpdateId);

            //before modifying the location checks, find the appropriate collection item with the corresponding checks
            foreach($colLocCheck as $colCheck){

                if($sameLoc == $colCheck['location']){
                    if ($location->checks != $colCheck['checks']) {
                        $location->checks = $colCheck['checks'];
                        $location->save();
                    }
                }
            }
        }

        if ($assigned->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    }

    public function deleteAssignedShift($id){

        $assigned = AssignedShift::find($id);

        //verify company first
        $verified = verifyCompany($assigned);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //find whether this assigned_shift has an entry in the shifts table, and retrieve the user_id of those shifts
        $commenced = DB::table('shifts')
            ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
            ->where('assigned_shifts.id', '=', $assigned->id)
            ->select('shifts.mobile_user_id')
            ->get();

        //shift has been commenced, so return to the console without deleting record
        if (count($commenced) > 0){

            return response()->json(['commenced' => 'commenced']);
        }

        $assigned->delete();

        AssignedEmp::where('assigned_shift_id', '=', $id)->delete();

        AssignedLoc::where('assigned_shift_id', '=', $id)->delete();

        //TODO: ensure record destroyed before returning success true
        return response()->json([
            'success' => true
        ]);
    }

    public function storeShiftResume($status, $shiftId){

        try {
            $shiftResume = new ShiftResume;

            $shiftResume->status = $status;
            $shiftResume->shift_id = $shiftId;
            $shiftResume->save();

            //retrieve id of the saved shift
            $id = $shiftResume->id;

            return $id;

        }catch (\Exception $e) {
        //Exception will catch all errors thrown
            return null;
        }
    }

    //this returns the last entry in the shift resume table for the user, regardless of whether shift has ended or not
    public function getLastShiftResumedByUser($userId){

        //get the last shift resumed by the user
        $lastShiftForUser = DB::table('shift_resumes')
            ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
            ->where('shifts.mobile_user_id', '=', $userId)
            ->where('shifts.deleted_at', '=', null)
            ->where('shift_resumes.deleted_at', '=', null)
            ->latest('shift_resumes.created_at')
            ->first();

        return $lastShiftForUser;

    }

    //get last shift resumed by the user
    //then ensure that shift has not ended
    //return: shiftId, assignedShiftId, shiftResumeCreatedAt
    public function getLastShiftResumed(Request $request){
        try{
            $userId = $request->input('userId');
            $user = User::find($userId);

            $verified = verifyCompany($user);

            if(!$verified){

                return response()->json($verified);//value = false
            }

            //get the last shift resumed by the user
            $lastShiftIdPerUser = DB::table('shift_resumes')
                ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
                ->select('shifts.id as shiftId',
                    'shift_resumes.created_at as shiftResumeCreatedAt', 'shifts.assigned_shift_id as assignedId')
                ->where('shifts.mobile_user_id', '=', $userId)
                ->where('shifts.deleted_at', '=', null)
                ->where('shift_resumes.deleted_at', '=', null)
                ->latest('shift_resumes.created_at')
                ->first();

            //then, if there is a value, ensure that shift has not ended
            if ($lastShiftIdPerUser != null) {

                $shiftIdNotEnded = DB::table('shifts')
                    ->select('id')
                    ->where('id', '=', $lastShiftIdPerUser->shiftId)
                    ->where('end_time', '=', null)
                    ->get();

                if(count($shiftIdNotEnded) != 0){

                    $shiftId = $shiftIdNotEnded[0]->id;

                    //1. lastShiftIdPerUser has a value and shiftId has a value
                    return response()->json([
                        'success' => true,
                        'shiftId' => $shiftId,
                        'lastShiftResumed' => $lastShiftIdPerUser->shiftId,//mostly for testing/checking purposes, this shift may be ended
                        'assignedId' => $lastShiftIdPerUser->assignedId,
                        'shiftResumeCreatedAt' => $lastShiftIdPerUser->shiftResumeCreatedAt
                    ]);
                }else {

                    //2. lastShiftIdPerUser has a value but shiftId does not have a value
                    return response()->json([
                        'success' => true,
                        'shiftIdEnded' => $shiftIdNotEnded,//the last shift resumed by the user has been ended, but return the empty array for thorough mobile testing
                        'lastShiftResumed' => $lastShiftIdPerUser->shiftId,//mostly for testing/checking purposes
                        'assignedId' => $lastShiftIdPerUser->assignedId,
                        'shiftResumeCreatedAt' => $lastShiftIdPerUser->shiftResumeCreatedAt
                    ]);
                }
            } else {
                //3. lastShiftIdPerUser has no value because user has no entries in the shiftResume table
                return response()->json([
                    'success' => true,
                    'shiftId' => null//the user does not have an entry in the shift resume table, so return null
                ]);
            }
        }catch (\Exception $e) {
            //Exception will catch all errors thrown
            return response()->json([
                'success' => false
            ]);
        }
    }

    //WIP
   /* public function shiftsPerUser($userId){
        //get the last shift resumed for a particular user
        $shiftsPerUser = DB::table('shift_resumes')
            ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
//                ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('shifts.mobile_user_id', '=', $userId)
            ->where('shifts.deleted_at', '=', null)
            ->where('shift_resumes.deleted_at', '=', null)
            ->get();

    }*/



}
